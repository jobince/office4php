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

class OLE2_Sector
{
    private $sectorSize;
    private $sid;
    private $isEOF;
    private $isFree;
    private $isSectorAllocationTable;
    private $isMasterSectorAllocationTable;

    function __construct(&$BufferedStream, $SectorSize, $SID)
    {
        // Acquisisce le informazioni sul file
        $this->sectorSize = $SectorSize;
        $this->sid = $SID;
        
        // Acquisisce le informazioni sul settore
        switch($SID)
        {
            // Il sid indica che il settore è vuoto e può essere
            // usato per la scrittura
            case -1:

                $this->isFree = true;
                break;

            // Il sid indica che il settore è usato come terminatore
            // dell'elenco
            case -2:

                $this->isEOF = true;
                break;

            // Il sid indica che il settore fa parte della sector
            // allocation table
            case -3:

                $this->isSectorAllocationTable = true;
                break;

            // Il sid indica che il settore fa parte della master
            // sector allocation table
            case -4:

                $this->isMasterSectorAllocationTable = true;
                break;
                
            default:

                // Calcola la posizione del settore
                $sectorPosition = $this->CalculateSectorPosition($this->sectorSize, $this->sid);

                // Si posiziona sul settore
                $BufferedStream->Seek($sectorPosition);
                
                // Estrae il singolo settore
                $buffer = $BufferedStream->Read($this->sectorSize);
                break;
        }
    }

    function Identifier()
    {
        // Restituisce l'identificatore del settore
        return $this->sid;
    }

    function Size()
    {
        // Restituisce la dimensione del settore
        return $this->sectorSize;
    }

    function IsFree()
    {
        // Restituisce la variabile isFree
        return $isFree;
    }

    function IsEOF()
    {
        // Restituisce la variabile isEOF
        return $isEOF;
    }

    function IsSectorAllocationTable()
    {
        // Restituisce la variabile isEOF
        return $isSectorAllocationTable;
    }

    function IsMasterSectorAllocationTable()
    {
        // Restituisce la variabile isEOF
        return $isMasterSectorAllocationTable;
    }
    
    public static function CalculateSectorPosition($SectorSize, $SID)
    {
        // I primi 512 bytes sono occupati dall'header e a questo
        // va sommato la posizione del settore ottenuta moltiplicando
        // la posizione ottenuta tramite il SID con la dimensione del
        // settore
        return 512 + ($SID * $SectorSize);
    }
}

?>