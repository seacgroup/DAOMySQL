<?php
/* 
 * The MIT Modified License (MIT, Erich Horn)
 * Copyright (c) 2012, 2013 Erich
 *
 * Author Erich
 * 
 * Permission is hereby granted, free of charge, to any person obtaining a copy 
 * of this software and associated documentation files (the "Software"), to 
 * deal in the Software without restriction, including without limitation the 
 * rights to use, copy, modify, merge, publish, distribute, sublicense, and/or 
 * sell copies of the Software, and to permit persons to whom the Software is 
 * furnished to do so, subject to the following conditions:
 * 
 * The above copyright notice, author and this permission notice shall be 
 * included in all copies or substantial portions of the Software.
 * 
 * THE SOFTWARE IS PROVIDED "AS IS", WITHOUT WARRANTY OF ANY KIND, EXPRESS OR 
 * IMPLIED, INCLUDING BUT NOT LIMITED TO THE WARRANTIES OF MERCHANTABILITY, 
 * FITNESS FOR A PARTICULAR PURPOSE AND NONINFRINGEMENT. IN NO EVENT SHALL THE 
 * AUTHORS OR COPYRIGHT HOLDERS BE LIABLE FOR ANY CLAIM, DAMAGES OR OTHER 
 * LIABILITY, WHETHER IN AN ACTION OF CONTRACT, TORT OR OTHERWISE, ARISING 
 * FROM, OUT OF OR IN CONNECTION WITH THE SOFTWARE OR THE USE OR OTHER DEALINGS 
 * IN THE SOFTWARE.
 */

/**
 *
 * @author Erich
 */
interface DAO
{
    public static function Columns ( );
    public static function Indexes ( );
    public static function Index ( $indexName );
    
    public static function FetchByPrimary ( $keyValue, $force = FALSE );
    public static function FetchMany ( Array $crit, $limit = FALSE, $offset = 0 );
    public static function FetchByIndex ( $indexName, Array $crit, $limit = FALSE, $offset = 0 );
    
    public static function StoreByPrimary ( Array $row, $fetchResult = TRUE );
    public static function StoreMany ( Array & $rows, Array $crit, $limit = FALSE, $offset = 0, $fetchResult = TRUE );
    public static function StoreByIndex ( Array & $rows, $indexName, Array $crit, $limit = FALSE, $offset = 0, $fetchResult = TRUE );
    
    public static function RemoveByPrimary ( $keyValue, $fetchRemoved = TRUE );
    public static function RemoveMany ( Array $crit, $limit = FALSE, $offset = 0, $fetchRemoved = TRUE );
    public static function RemoveByIndex ( $indexName, Array $crit, $limit = FALSE, $offset = 0, $fetchRemoved = TRUE );
    
}
?>
