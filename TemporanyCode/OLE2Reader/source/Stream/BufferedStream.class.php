<?php
/**
 * Copyright (c) 2007-2008, Albano Daniele Salvatore aka daniele_dll <d.albano@phpsoft.it>
 * 
 * All rights reserved.
 * 
 * Redistribution and use in source and binary forms, with or without modification, are permitted provided that the following conditions are met:
 * 
 *     * Redistributions of source code must retain the above copyright notice, this list of conditions and the following disclaimer.
 *     * Redistributions in binary form must reproduce the above copyright notice, this list of conditions and the following disclaimer in the documentation and/or other materials provided with the distribution.
 *     * Neither the name of the <ORGANIZATION> nor the names of its contributors may be used to endorse or promote products derived from this software without specific prior written permission.
 * 
 * THIS SOFTWARE IS PROVIDED BY THE COPYRIGHT HOLDERS AND CONTRIBUTORS
 * "AS IS" AND ANY EXPRESS OR IMPLIED WARRANTIES, INCLUDING, BUT NOT
 * LIMITED TO, THE IMPLIED WARRANTIES OF MERCHANTABILITY AND FITNESS FOR
 * A PARTICULAR PURPOSE ARE DISCLAIMED. IN NO EVENT SHALL THE COPYRIGHT OWNER OR
 * CONTRIBUTORS BE LIABLE FOR ANY DIRECT, INDIRECT, INCIDENTAL, SPECIAL,
 * EXEMPLARY, OR CONSEQUENTIAL DAMAGES (INCLUDING, BUT NOT LIMITED TO,
 * PROCUREMENT OF SUBSTITUTE GOODS OR SERVICES; LOSS OF USE, DATA, OR
 * PROFITS; OR BUSINESS INTERRUPTION) HOWEVER CAUSED AND ON ANY THEORY OF
 * LIABILITY, WHETHER IN CONTRACT, STRICT LIABILITY, OR TORT (INCLUDING
 * NEGLIGENCE OR OTHERWISE) ARISING IN ANY WAY OUT OF THE USE OF THIS
 * SOFTWARE, EVEN IF ADVISED OF THE POSSIBILITY OF SUCH DAMAGE.
 */

require_once('BaseStream.class.php');

class BufferedStream extends BaseStream
{
    // Costanti interne
    const DEFAULT_BUFFER_SIZE = 8192;

    // Variabili interne
    private $bufferStartPosition;
    private $bufferContentLenght;
    private $bufferLenght;
    private $bufferIsSynced;
    private $bufferData;
    private $fileName;
    private $fileLenght;
    private $filePointer;
    private $fileIsOpened;
    private $fileIsLocked;
    private $fileLockIsExclusive;
    private $pointerPosition;

    function __construct($FileName, $BufferLenght = self::DEFAULT_BUFFER_SIZE)
    {
        // Verifica che il file esista
        if (file_exists($FileName) === false)
        {
            throw new BufferedStreamMissingFileException($FileName);
        }
        
        // Imposta il nome del file e la dimensione del buffer
        $this->fileName = $FileName;
        $this->bufferLenght = $BufferLenght;
        
        // Imposta i settaggi interni
        $this->fileLenght = 0;
        $this->fileIsOpened = false;
        $this->fileIsLocked = false;
        $this->fileLockIsExclusive = false;
        $this->bufferIsSynced = false;
        $this->bufferStartPosition = 0;
        $this->bufferContentLenght = 0;
    }
    
    function __destruct()
    {
        // Verifica se il file è aperto
        if ($this->fileIsOpened === true)
        {
            // Verifica se il buffer è sincronizzato
            if ($this->bufferIsSynced === false)
            {
                $this->Sync();
            }

            // Verifica se è il file è lockato
            if ($this->fileIsLocked === true)
            {
                $this->Unlock();
            }
            
            // Chiude il file aperto
            $this->Close();
        }
    }

    function Open($Mode = 'rb+')
    {
        // Verifica se il file è aperto
        if ($this->fileIsOpened === true)
        {
            throw new BufferedStreamFileOpenedException($this->fileName);
        }

        // Apre il file
        $this->filePointer = fopen($this->fileName, $Mode);

        // Imposta i settaggi interni
        $this->fileLenght = sprintf('%u', filesize($this->fileName));
        $this->fileIsOpened = true;
        $this->fileIsLocked = false;
        $this->fileLockIsExclusive = false;
        $this->bufferIsSynced = true;
        $this->bufferStartPosition = 0;
        $this->bufferContentLenght = 0;
    }

    function Lock($Exclusive = false)
    {
        // Verifica se il file è aperto
        if ($this->fileIsOpened === false)
        {
            throw new BufferedStreamFileNotOpenedException($this->fileName);
        }

        // Verifica se il file è già lockato
        if ($this->fileIsLocked === true)
        {
            throw new BufferedStreamIsLockedException($this->fileName);
        }

        // Locka il file
        $result = flock($this->filePointer, ($Exclusive === true) ? LOCK_EX : LOCK_SH);

        if ($result === false)
        {
            throw new BufferedStreamUnableToLockException($this->fileName, $Exclusive);
        }
        
        // Imposta il file come locked
        $this->fileIsLocked = true;
        $this->fileLockIsExclusive = $Exclusive;
    }

    function Read($Lenght, &$ReadedLenght = false)
    {
        // Verifica se il file è aperto
        if ($this->fileIsOpened === false)
        {
            throw new BufferedStreamFileNotOpenedException($this->fileName);
        }

        // Se il puntatore è arrivato alla fine, imita la funzione fread per
        // ritornare che non vi è altro contenuto
        if ($this->pointerPosition == $this->fileLenght)
        {
            return false;
        }


        // Verifica se il blocco di dati richiesto è più grande del buffer
        if ($Lenght > $this->bufferLenght)
        {
            // Sincronizza il buffer
            $this->Sync();

            // Resetta il buffer per forzarne la ricreazione alla
            // prossima acquisizione dei dati se questa sarà di
            // lunghezza inferiore
            $this->bufferContentLenght = 0;
            $this->bufferStartPosition = 0;

            // Legge il contenuto dal disco
            $result = fread($this->filePointer, $Lenght);

            // Verifica se c'è stato errore
            if ($result === false)
            {
                throw new BufferedStreamReadException($this->fileName, $Lenght);
            }

            // Imposta il $ReadedLenght
            if ($ReadedLenght !== false)
            {
                $ReadedLenght = strlen($result);
            }

            // Aggiorna la posizione del puntatore
            $this->pointerPosition += $Lenght;
            
            // Se la posizione del puntatore supera la lunghezza del file
            // resetta la posizione del puntatore
            if ($this->pointerPosition > $this->fileLenght - 1)
            {
                $this->pointerPosition = $this->fileLenght - 1;
            }

            // Restituisce il contenuto acquisito
            return $result;
        }
        else
        {
            // Verifica se può essere utilizzato il buffer di lettura
            if (
                    $this->bufferContentLenght == 0
                    ||
                    $this->pointerPosition < $this->bufferStartPosition
                    ||
                    ($this->pointerPosition + $Lenght) > ($this->bufferStartPosition + $this->bufferContentLenght)
                )
            {
                // Sincronizza il buffer
                $this->Sync();
                
                // Posiziona il puntatore
                fseek($this->filePointer, $this->pointerPosition);

                // Salva la nuova area del buffer
                $result = fread($this->filePointer, $this->bufferLenght);

                // Verifica se c'è stato errore
                if ($result === false)
                {
                    throw new BufferedStreamReadException($this->fileName, $Lenght);
                }
                
                // Salva il buffer
                $this->bufferData = &$result;

                // Imposta le nuove informazioni sul buffer
                $this->bufferStartPosition = (int)$this->pointerPosition;
                $this->bufferContentLenght = (int)strlen($this->bufferData);

                // Aggiorna la posizione del puntatore
                $this->pointerPosition += $Lenght;

                // Imposta il $ReadedLenght
                if ($ReadedLenght !== false)
                {
                    $ReadedLenght = $Lenght > $this->bufferContentLenght ? $this->bufferContentLenght : $Lenght;
                }

                // Se la posizione del puntatore supera la lunghezza del file
                // resetta la posizione del puntatore
                if ($this->pointerPosition > $this->fileLenght)
                {
                    $this->pointerPosition = $this->fileLenght;
                }

                // Restituisce l'area letta
                return substr($this->bufferData, 0, $Lenght);
            }

            // Estrae il contenuto del buffer
            $result = substr($this->bufferData, $this->pointerPosition - $this->bufferStartPosition, $Lenght);

            // Aggiorna la posizione del puntatore
            $this->pointerPosition += $Lenght;

            // Imposta il $ReadedLenght
            if ($ReadedLenght !== false)
            {
                $ReadedLenght = strlen($Lenght);
            }

            // Se la posizione del puntatore supera la lunghezza del file
            // resetta la posizione del puntatore
            if ($this->pointerPosition > $this->fileLenght)
            {
                $this->pointerPosition = $this->fileLenght;
            }

            // Restituisce il risultato
            return $result;
        }
    }

    function Write()
    {
        // Verifica se il file è aperto
        if ($this->fileIsOpened === false)
        {
            throw new BufferedStreamFileNotOpenedException($this->fileName);
        }
    }

    function Seek($Position, $From = SEEK_SET)
    {
        // Verifica se il file è aperto
        if ($this->fileIsOpened === false)
        {
            throw new BufferedStreamFileNotOpenedException($this->fileName);
        }

        // Sincronizza il buffer
        $this->Sync();

        // Sposta il puntatore all'interno del file
        $result = fseek($this->filePointer, $Position, $From);
        
        // Verifica se l'operazione è riuscita
        if ($result === -1)
        {
            throw new BufferedStreamSeekException($this->fileName, $Position, $From);
        }
        
        // Acquisisce la nuova posizione del puntatore
        $this->pointerPosition = ftell($this->filePointer);

        // Resetta il buffer per forzarne la ricreazione alla
        // prossima acquisizione dei dati
        $this->bufferContentLenght = 0;
        $this->bufferStartPosition = 0;
    }

    function Unlock()
    {
        // Verifica se il file è aperto
        if ($this->fileIsOpened === false)
        {
            throw new BufferedStreamFileNotOpenedException($this->fileName);
        }

        // Verifica se il file è già lockato
        if ($this->fileIsLocked === false)
        {
            throw new BufferedStreamIsntLockedException($this->fileName);
        }

        // Unlocka il file
        $result = flock($this->filePointer, LOCK_UN);

        if ($result === false)
        {
            throw new BufferedStreamUnableToUnlockException($this->fileName);
        }
    }
    
    function Sync()
    {
        // Verifica se è necessario scrivere il buffer
        if ($this->bufferIsSynced === true)
        {
            return;
        }

        // Si sposta nell'area di inizio del buffer
        fseek($this->filePointer, $this->bufferStartPosition);

        // Scrive il buffer
        fwrite($this->filePointer, $this->bufferData, $this->bufferContentLenght);
        
        // Segnala che il buffer è sincronizzato
        $this->bufferIsSynced = true;
    }

    function Close()
    {
        // Verifica se il file è aperto
        if ($this->fileIsOpened === false)
        {
            throw new BufferedStreamFileNotOpenedException($this->fileName);
        }

        // Sincronizza il buffer
        $this->Sync();

        // Chiude il file
        fclose($this->filePointer);

        // Imposta i settaggi interni
        $this->fileLenght = 0;
        $this->fileIsOpened = false;
        $this->fileIsLocked = false;
        $this->fileLockIsExclusive = false;
        $this->bufferIsSynced = true;
        $this->bufferStartPosition = 0;
        $this->bufferContentLenght = 0;
    }

    function EOF()
    {
        // Verifica se il file è aperto
        if ($this->fileIsOpened === false)
        {
            throw new BufferedStreamFileNotOpenedException($this->fileName);
        }

        // Verifica se il file è stato letto per intero
        if (feof($this->filePointer) === true)
        {
            // Verifica se il buffer contiene dati o meno
            if ($this->bufferContentLenght > 0)
            {
                if ($this->pointerPosition === $this->bufferStartPosition + $this->bufferContentLenght)
                {
                    // Fine del file raggiunta
                    return true;
                }
            }
            else
            {
                return true;
            }
        }

        // Fine del file non raggiunta
        return false;
    }

    function Position()
    {
        // Restituisce la posizione del puntatore
        return $this->pointerPosition;
    }

    function Name()
    {
        // Restituisce il nome del file
        return $this->fileName;
    }

    function Lenght()
    {
        // Restituisce il nome del file
        return $this->fileLenght;
    }

    function IsOpened()
    {
        // Restituisce se il file è aperto o meno
        return $this->fileIsOpened;
    }

    function IsSynced()
    {
        // Restituisce la sincronizzazione eseguita o meno del disco
        return $this->bufferIsSynced;
    }

    function IsLocked()
    {
        // Restituisce se il file è lockato o meno
        return $this->fileIsLocked;
    }

    function LockIsExclusive()
    {
        // Restituisce il tipo di lock eseguito
        return $this->fileLockIsExclusive;
    }
}

// BufferedStreams::__construct
class BufferedStreamMissingFileException extends Exception
{
    public function __construct($FileName)
    {
       parent::__construct("Il file {$FileName} non esiste", 0);
    }
}

// BufferedStreams::Open
class BufferedStreamFileOpenedException extends Exception
{
    public function __construct($FileName)
    {
       parent::__construct("Non è possibile aprire il file {$FileName}", 0);
    }
}

// BufferedStreams::Lock/Unlock/Read/Write/EOF/Seek/Lenght/Close
class BufferedStreamFileNotOpenedException extends Exception
{
    public function __construct($FileName)
    {
       parent::__construct("Il file {$FileName} non è stato ancora aperto tramite il metodo BufferedStream::Open", 0);
    }
}

// BufferedStreams::Lock/Unlock/Read/Write/EOF/Seek/Lenght/Close
class BufferedStreamIsLockedException extends Exception
{
    public function __construct($FileName)
    {
       parent::__construct("Il file {$FileName} non può essere unlockato", 0);
    }
}

// BufferedStreams::Lock/Unlock/Read/Write/EOF/Seek/Lenght/Close
class BufferedStreamUnableToLockException extends Exception
{
    public function __construct($FileName, $Exclusive)
    {
       parent::__construct("Non è possibile eseguire il locking del file {$FileName} in modalità " . ($Exclusive === true) ? 'esclusiva' : 'condivisa', 0);
    }
}

// BufferedStreams::Lock/Unlock/Read/Write/EOF/Seek/Lenght/Close
class BufferedStreamIsntLockedException extends Exception
{
    public function __construct($FileName)
    {
       parent::__construct("Il file {$FileName} non può essere unlockato", 0);
    }
}

// BufferedStreams::Lock/Unlock/Read/Write/EOF/Seek/Lenght/Close
class BufferedStreamUnableToUnlockException extends Exception
{
    public function __construct($FileName)
    {
       parent::__construct("Il file {$FileName} non può essere unlockato", 0);
    }
}

// BufferedStreams::Lock/Unlock/Read/Write/EOF/Seek/Lenght/Close
class BufferedStreamReadException extends Exception
{
    public function __construct($FileName, $Lenght)
    {
       parent::__construct("Impossibile leggere {$Lenght} byte dal {$FileName}", 0);
    }
}

// BufferedStreams::Lock/Unlock/Read/Write/EOF/Seek/Lenght/Close
class BufferedStreamSeekException extends Exception
{
    public function __construct($FileName, $Position, $From)
    {
       parent::__construct("Impossibile spostarsi alla posizione {$Position} " . ($From === SEEK_SET ? 'dall\'inizio del file' : ($From === SEEK_CUR ? 'dalla posizione corrente' : 'dalla fine del file')) . " all'interno del file {$FileName}", 0);
    }
}

?>