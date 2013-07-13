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
 * Description of DAOMySQL
 *
 * @author Erich
 */
abstract
class ADAOMySQL {
    
    /**
     * 
     * @param type $options
     * @return boolean
     */
    public static function Connect($options = []) {
        if (empty(static::$_Connection)) {
            if (!isset($options['dbname']) || !is_string($options['dbname']))
                $databaseName = defined($const = get_called_class() . '::DATABASE_NAME') ? constant($const) : '';
            if (!( static::$_Connection = new mysqli(
                    isset($options['host']) ? $options['host'] : ini_get("mysqli.default_host"), isset($options['user']) ? $options['user'] : ini_get("mysqli.default_user"), isset($options['pass']) ? $options['pass'] : ini_get("mysqli.default_pw"), $databaseName, isset($options['port']) ? $options['port'] : ini_get("mysqli.default_port")
                    ) ))
                return FALSE;
        }
        
        return TRUE;
    }
    
    /**
     * 
     * @return type
     */
    public static function Close() {
        $result = empty(static::$_Connection) ? TRUE : static::$_Connection->close();
        static::$_Connection = NULL;
        return $result;
    }
    
    public static function ToValue ( $value )
    {
        if ( $value === NULL )
            return 'NULL';
        if ( is_string( $value ) )
            return "'" . addslashes( $value ) . "'";
        if ( is_bool( $value ) )
            return $value ? 1 : 0;
        if ( is_numeric( $value ) )
            return $value;
        return json_encode( $value );
    }
    
    /**
     * 
     * @param array $row
     * @return string
     */
    public static function ToNameValuePairs ( Array $row )
    {
        $pairs = [ ];
        foreach ( array_keys( static::$_Columns ) as $name )
            if ( isset( $row[$name] ) )
                $pairs[] = '`' . $name . "` = " . self::ToValue( $row[$name] );
        return $pairs;
    }
    
    /**
     * 
     * @param array $row
     * @return string
     */
    public static function ToCriteria ( Array $row )
    {
        $pairs = [ ];
        foreach ( array_keys( static::$_Columns ) as $name )
            if ( isset( $row[$name] ) )
            {
                if ( $row[$name] === NULL )
                    $pairs[] = '`' . $name . "` IS NULL";
                else
                    $pairs[] = '`' . $name . "` = " . self::ToValue( $row[$name] );
            }
        return $pairs;
    }
    
    /**
     * 
     */
    public static function Columns() {
        return array_keys( static::$_Columns );
    }
    
    /**
     * 
     */
    public static function Indexes() {
        return array_keys( static::$_Indexes );
    }
    
    /**
     * 
     * @param string $indexName
     */
    public static function Index($indexName) {
        return static::$_Indexes[$indexName];
    }
    
    /**
     * 
     * @param array $crit
     * @param int $force
     */
    public static function FetchByPrimary($keyValue, $force = FALSE) {
        $query = 'SELECT * FROM `' . static::TABLE_NAME . '` WHERE `' . static::PRIMARY_KEY . '` = ' . self::ToValue( $keyValue );
        $row = NULL;
        if ( ( $result = static::$_Connection->query( $query, MYSQLI_USE_RESULT ) ) ) {
            $row = $result->fetch_assoc( );
            $result->close();
        }
        return $row;
    }
    
    /**
     * 
     * @param array $crit
     * @param int $limit
     * @param int $offset
     * @param int $force
     */
    public static function FetchMany(Array $crit, $limit = FALSE, $offset = 0, $force = FALSE) {
        
    }
    
    /**
     * 
     * @param string $indexName
     * @param array $crit
     * @param int $limit
     * @param int $offset
     * @param int $force
     */
    public static function FetchByIndex($indexName, Array $crit, $limit = FALSE, $offset = 0, $force = FALSE) {
        
    }
    
    /**
     * 
     * @param array $row
     * @param mixed $crit
     * @param boolean $fetchResult
     */
    public static function StoreByPrimary(Array $row, $fetchResult = TRUE) {
        if ( isset( $row[static::PRIMARY_KEY] ) && ( $keyValue = $row[static::PRIMARY_KEY] ) ) {
            $query = 'UPDATE `' . static::TABLE_NAME . '` SET ' . implode( ', ', self::ToNameValuePairs( $row ) ) . 'WHERE `' . static::PRIMARY_KEY . '` = ' . self::ToValue( $keyValue );
//            var_dump( $query );
            if ( ! static::$_Connection->query( $query, MYSQLI_STORE_RESULT ) )
                return FALSE;
        } else {
            $query = 'INSERT INTO `' . static::TABLE_NAME . '` ( `' . implode( '`, `', array_keys( $row ) ) . "` ) VALUES ( '" . implode( "', '", array_values( $row ) ) . "' );";
//            var_dump( $query );
            if ( ! static::$_Connection->query( $query, MYSQLI_STORE_RESULT )
                    || ! ( $keyValue = static::$_Connection->insert_id ) )
                return FALSE;
        }
        return $fetchResult ? self::FetchByPrimary( $keyValue ) : $keyValue;
    }
    
    /**
     * 
     * @param array $rows
     * @param array $crit
     * @param int $limit
     * @param int $offset
     * @param boolean $fetchResult
     */
    public static function StoreMany(Array & $rows, Array $crit, $limit = FALSE, $offset = 0, $fetchResult = TRUE) {
        
    }
    
    /**
     * 
     * @param array $rows
     * @param string $indexName
     * @param array $crit
     * @param int $limit
     * @param int $offset
     * @param boolean $fetchResult
     */
    public static function StoreByIndex(Array & $rows, $indexName, Array $crit, $limit = FALSE, $offset = 0, $fetchResult = TRUE) {
        
    }
    
    /**
     * 
     * @param array $crit
     * @param boolean $fetchRemoved
     */
    public static function RemoveByPrimary($keyValue, $fetchRemoved = TRUE) {
        $result = TRUE;
        if ( $fetchRemoved )
            $result = self::FetchByPrimary( $keyValue );
        $query = 'DELETE FROM `' . static::TABLE_NAME . '` WHERE `' . static::PRIMARY_KEY . '` = ' . self::ToValue( $keyValue );
        if ( ! $result || ! static::$_Connection->query( $query, MYSQLI_STORE_RESULT ) )
            return FALSE;
        return $result;
    }
    
    /**
     * 
     * @param array $crit
     * @param int $limit
     * @param int $offset
     * @param boolean $fetchRemoved
     */
    public static function RemoveMany(Array $crit, $limit = FALSE, $offset = 0, $fetchRemoved = TRUE) {
        
    }
    
    /**
     * 
     * @param string $indexName
     * @param array $crit
     * @param int $limit
     * @param int $offset
     * @param boolean $fetchRemoved
     */
    public static function RemoveByIndex($indexName, Array $crit, $limit = FALSE, $offset = 0, $fetchRemoved = TRUE) {
        
    }

}
