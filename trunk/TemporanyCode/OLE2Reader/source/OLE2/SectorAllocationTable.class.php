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

class OLE2_MasterSectorAllocationTable
{
    private $sidChain;

    function __construct(&$BufferedStream, $SectorSize)
    {
        // Dato che questa classe viene instanziata subito
        // dopo che l'header viene letto si è già posizionati
        // all'inizio della lista, quindi non è necessario
        // effettuare alcun spostamento
        
        // Inizializza la catena dei chain
        $this->sidChain = array();

        // Inizializza il SID da leggere dopo il primo settore
        $nextSector = 0;
        
        // Crea un counter per verificare il numero di SID
        // letti
        $sidsToRead = 0;

        // Avvia il ciclo di lettura
        while($nextSector >= 0)
        {
            // Verifica se deve posizionarsi
            if ($nextSector != 0)
            {
                // Calcola la posizione nello stream del nuovo settore
                // e si ci sposta
                $BufferedStream->Seek(OLE2_Sector::CalculateSectorPosition($SectorSize, $nextSector));

                // Inizializza il counter dei sid da leggere su
                $sidsToRead = 128;
            }
            else
            {
                // Inizializza il counter dei sid da leggere su
                $sidsToRead = 109;
            }

            // Avvia la lettura dei settori
            for ($sidIndex = 0; $sidIndex < $sidsToRead; $sidIndex++)
            {
                // Inizia a leggere il contenuto del settore iniziale
                $sid = ByteConverter::ConvertToSignedInteger($BufferedStream->Read(4));

                // Verifica se è un settore o un indicatore di terminazione
                if ($sid < 0)
                {
                    // Blocca il ciclo FOR
                    break;
                }
                // Aggiunge il sid all'elenco se questo non è alla fine del settore
                // dato che l'ultimo sid alla fine del settore serve a indicare
                // la posizione del settore dell'MSAT successivo
                elseif ($sidIndex < ($sidsToRead - 1))
                {
                    $this->sidChain[] = $sid;
                }
            }

            // Imposta il nuovo settore
            $nextSector = $sid;
        }
    }
    
    function GetChain()
    {
        // Restituisce la catena di SID
        return $sidChain;
    }
}

?>