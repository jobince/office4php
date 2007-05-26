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

abstract class BaseStream
{
    function Open($Mode)
    {
        throw new BaseStreamNotImplementedException('Open');
    }

    function Lock($Exclusive)
    {
        throw new BaseStreamNotImplementedException('Lock');
    }

    function Read($Lenght, &$ReadedLenght)
    {
        throw new BaseStreamNotImplementedException('Read');
    }

    function Write()
    {
        throw new BaseStreamNotImplementedException('Write');
    }

    function Seek($Position, $From)
    {
        throw new BaseStreamNotImplementedException('Seek');
    }

    function Unlock()
    {
        throw new BaseStreamNotImplementedException('Unlock');
    }

    function Sync()
    {
        throw new BaseStreamNotImplementedException('Sync');
    }

    function Close()
    {
        throw new BaseStreamNotImplementedException('Close');
    }

    function EOF()
    {
        throw new BaseStreamNotImplementedException('EOF');
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

// BaseStream General Exception
class BaseStreamNotImplementedException extends Exception
{
    public function __construct($FileName)
    {
       parent::__construct("Il metodo {$MethodName} non è stato implementato", 0);
    }
}

?>