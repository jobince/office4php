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
 
class OLE2_Header
{
    private $bufferedStream;

    private $uid;                                   // UID del documento
    private $revision;                              // Revisione del documento
    private $version;                               // Versione del documento
    private $sectorSize;                            // Dimensione del settore in byte
    private $shortSectorSize;                       // Dimensione dei settori piccoli in byte

    private $totalSectors;                          // Numero totale dei settori
    private $rootDirectoryFirstSIDSector;           // SID del primo settore dello stream delle directory

    private $minimiumStreamSize;                    // Dimensine minima di uno stream (se lo stream è più piccolo
                                                    //                                 questi vanno conservati
                                                    //                                 come short stream)
    private $shortStreamFirstSIDSector;             // Primo settore del SID dell'elenco dello short stream
    private $totalShortSectors;                     // Numero totale degli short sectors

    private $masterAllocationTableFirstSIDSector;   // SID del primo settore della master allocation table
    private $totalMasterAllocationTableSectors;     // Numero di settori totale nella master allocation table

    function __construct(&$BufferedStream)
    {
        // Acquisisce il buffered stream
        $this->bufferedStream = &$BufferedStream;

        // Legge l'intestazione del file
        $magicHeader = $this->bufferedStream->Read(8);

        // Verifica che l'intestazione sia valida
        if ($magicHeader !== "\xD0\xCF\x11\xE0\xA1\xB1\x1A\xE1")
        {
            throw new Ole2HeaderWrongException($BufferedStream);
        }

        // Acquisisce l'UID del documento
        $this->uid = $this->bufferedStream->Read(16);

        // Acquisisce la revisione e la versione
        $this->revision = ByteConverter::ConvertToSignedShort($this->bufferedStream->Read(2));
        $this->version = ByteConverter::ConvertToSignedShort($this->bufferedStream->Read(2));

        // Verifica il tipo di ordinamento dei byte
        $byteOrder = $this->bufferedStream->Read(2);

        if ($byteOrder === "\xFF\xFE")
        {
            throw new Ole2HeaderByteOrderNotSupportedException($BufferedStream);
        }

        // Legge la dimensione dei settori
        $sectorSizeExponent = ByteConverter::ConvertToSignedShort($this->bufferedStream->Read(2));
        $this->sectorSize = pow(2, $sectorSizeExponent);

        // Legge la dimensione dei settori piccoli
        $shortSectorSizeExponent = ByteConverter::ConvertToSignedShort($this->bufferedStream->Read(2));
        $this->shortSectorSize = pow(2, $shortSectorSizeExponent);

        // Legge 10 bytes fittizzi (conviene più leggere 10 bytes fittizzi che spostarsi tramite
        // l'apposito metodo Seek perché quest'ultimo droppa il buffer in qualsiasi caso e questo
        // comporterebbe una rilettura dal disco invece che un semplice substr di un array)
        $this->bufferedStream->Read(10);

        // Acquisisce le informazioni sulla tabella di allocazione dei settori e sul primo
        // SID del primo settore della directory stream
        $this->totalSectors = ByteConverter::ConvertToSignedInteger($this->bufferedStream->Read(4));
        $this->rootDirectoryFirstSIDSector = ByteConverter::ConvertToSignedInteger($this->bufferedStream->Read(4));

        // Un'altro intermezzo di dati inutili o per meglio dire attualmente riservati
        $this->bufferedStream->Read(4);

        // Acquisisce le informazioni sui short-sectors
        $this->minimiumStreamSize = ByteConverter::ConvertToSignedInteger($this->bufferedStream->Read(4));
        $this->shortStreamFirstSIDSector = ByteConverter::ConvertToSignedInteger($this->bufferedStream->Read(4));
        $this->totalShortSectors = ByteConverter::ConvertToSignedInteger($this->bufferedStream->Read(4));

        // Acquisisce le informazioni sulla tabella principale dei settori
        $this->masterAllocationTableFirstSIDSector = ByteConverter::ConvertToSignedInteger($this->bufferedStream->Read(4));
        $this->totalMasterAllocationTableSectors = ByteConverter::ConvertToSignedInteger($this->bufferedStream->Read(4));
    }

    public function UID()
    {
        return $this->uid;
    }

    public function Revision()
    {
        return $this->revision;
    }

    public function Version()
    {
        return $this->version;
    }

    public function SectorSize()
    {
        return $this->sectorSize;
    }

    public function ShortSectorSize()
    {
        return $this->shortSectorSize;
    }

    public function TotalSectors()
    {
        return $this->totalSectors;
    }

    public function RootDirectoryFirstSIDSector()
    {
        return $this->rootDirectoryFirstSIDSector;
    }

    public function MinimiumStreamSize()
    {
        return $this->minimiumStreamSize;
    }

    public function ShortStreamFirstSIDSector()
    {
        return $this->shortStreamFirstSIDSector;
    }

    public function TotalShortSectors()
    {
        return $this->totalShortSectors;
    }

    public function MasterAllocationTableFirstSIDSector()
    {
        return $this->masterAllocationTableFirstSIDSector;
    }

    public function TotalMasterAllocationTableSectors()
    {
        return $this->totalMasterAllocationTableSectors;
    }
}

// Ole2Header::__construct
class Ole2HeaderWrongException extends Exception
{
    public function __construct(&$BufferedStream)
    {
       parent::__construct("Il file {$BufferedStream->Name} non è un file OLE2 valido", 0);
    }
}

// Ole2Header::__construct
class Ole2HeaderByteOrderNotSupportedException extends Exception
{
    public function __construct(&$BufferedStream)
    {
       parent::__construct("Il file {$BufferedStream->Name} fa uso di un ordinamento dei byte non supportato", 0);
    }
}

?>