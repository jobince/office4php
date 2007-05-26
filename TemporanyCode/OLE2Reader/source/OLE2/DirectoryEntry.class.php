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

class OLE2_DirectoryEntry
{
    // Elenco di tipi disponibili
    const TYPE_EMPTY        = 0;
    const TYPE_STORAGE      = 1;
    const TYPE_STREAM       = 2;
    const TYPE_LOCKBYTES    = 3;
    const TYPE_PROPERTY     = 4;
    const TYPE_ROOTSTORAGE  = 5;

    // Variabili interne
    private $name;
    private $type;
    private $id;
    private $flags;
    private $creationTimestamp;
    private $lastModificationTimestamp;
    private $sectorFirstSID;
    private $length;

    function __construct(&$BufferedStream)
    {
        // Legge il nome
        $this->name = utf8_decode($BufferedStream->Read(64));

        // Dati inutili (almeno per php)
        $BufferedStream->Read(2);

        // Legge il tipo di entry
        $this->type = $BufferedStream->Read(1);

        // FIXME: legge le directory figlie ed il root node
        $BufferedStream->Read(4+4+4);

        // Legge lo Unique Identifier
        $this->id = $BufferedStream->Read(16);

        // Legge i flag
        $this->flags = ByteConverter::ConvertToSignedInteger($BufferedStream->Read(4));

        // FIXME: legge i timestamp di creazione e modifica
        $BufferedStream->Read(4+4+4);

        // Legge il sid del primo settore
        $this->sectorFirstSID = $BufferedStream->Read(4);

        // Legge la dimensione dello stream
        $this->length = $BufferedStream->Read(4);
    }
    
    function Name()
    {
        return $this->name;
    }

    function Type()
    {
        return $this->type;
    }

    function ID()
    {
        return $this->id;
    }

    function Flags()
    {
        return $this->flags;
    }

    function CreationTimestamp()
    {
        return $this->creationTimestamp;
    }

    function LastModificationTimestamp()
    {
        return $this->lastModificationTimestamp;
    }

    function SectorFirstSID()
    {
        return $this->sectorFirstSID;
    }

    function Length()
    {
        return $this->length;
    }
}

?>