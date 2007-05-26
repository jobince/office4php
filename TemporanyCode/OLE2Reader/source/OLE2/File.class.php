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

require_once(dirname(__FILE__) . '/../Stream/BaseStream.class.php');
require_once(dirname(__FILE__) . '/../Stream/BufferedStream.class.php');
require_once(dirname(__FILE__) . '/../Utils/ByteConverter.class.php');
require_once(dirname(__FILE__) . '/Header.class.php');
require_once(dirname(__FILE__) . '/MasterSectorAllocationTable.class.php');
require_once(dirname(__FILE__) . '/SectorChain.class.php');
require_once(dirname(__FILE__) . '/Sector.class.php');
require_once(dirname(__FILE__) . '/DirectoryEntryList.class.php');
require_once(dirname(__FILE__) . '/DirectoryEntry.class.php');

class OLE2_File
{
    private $bufferedStream;
    private $ole2Header;
    private $ole2MSAT;

    function __construct(&$Object)
    {
        if (is_a($Object, 'BufferedStream') === true)
        {
            // Acquisisce il buffered stream passato
            $this->bufferedStream = &$Object;
        }
        else
        {
            // Inizializza lo buffered stream
            $this->bufferedStream = new BufferedStream($Object);
        }
    }

    function Open()
    {
        // Verifica se il file è aperto
        if ($this->bufferedStream->IsOpened() === false)
        {
            // Apre il file
            $this->bufferedStream->Open();
        }

        // Importa l'header
        $this->ole2Header = new OLE2_Header($this->bufferedStream);
        
        // Imposta la MSAT
        $this->ole2MSAT =  new OLE2_MasterSectorAllocationTable($this->bufferedStream, $this->ole2Header->SectorSize());
        
        var_dump($this->ole2MSAT);
    }

    function Close()
    {
        if ($this->bufferedStream->IsOpened() === true)
        {
            // Chiude il file
            $this->bufferedStream->Close();
        }
    }

}

?>