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
    public static function Connect( $options = [ ] ) {
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
    public static function Close ( ) {
        $result = empty(static::$_Connection) ? TRUE : static::$_Connection->close();
        static::$_Connection = NULL;
        return $result;
    }
    
    /**
     * 
     * @param type $value
     * @return string
     */
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
     * @param type $values
     */
    public static function ToValueList ( $values )
    {
        $result = [ ];
        foreach ( $values as $value )
        {
            if ( $value === NULL )
                $result[] = 'NULL';
            elseif ( is_string( $value ) )
                $result[] = "'" . addslashes( $value ) . "'";
            elseif ( is_bool( $value ) )
                $result[] = $value ? 1 : 0;
            elseif ( is_numeric( $value ) )
                $result[] = $value;
            else
                $result[] = json_encode( $value );
        }
        
        return $result;
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
    public static function ToCriteria ( Array $crit )
    {
        $pairs = [ ];
        foreach ( array_keys( static::$_Columns ) as $name => $finfo )
            if ( isset( $crit[$name] ) )
            {
                if ( $crit[$name] === NULL )
                    $pairs[] = '`' . $name . "` IS NULL";
                elseif ( is_array( $crit[$name] ) ) {
                    $set = [ ];
                    foreach ( $crit[$name] as $value )
                        $set[] = self::ToValue( $value );
                    $pairs[] = '`' . $name . '` IN ( ' . $set . ' )';
                } else
                    $pairs[] = '`' . $name . "` = " . self::ToValue( $crit[$name] );
            }
        return $pairs;
    }
    
    /**
     * 
     * @param array $row
     * @return string
     */
    public static function ToIndexCriteria ( $indexName, Array $crit )
    {
        $pairs = [ ];
        foreach ( array_keys( static::$_Indexes ) as $name )
            if ( isset( $crit[$name] ) )
            {
                if ( $crit[$name] === NULL )
                    $pairs[] = '`' . $name . "` IS NULL";
                elseif ( is_array( $crit[$name] ) ) {
                    $set = [ ];
                    foreach ( $crit[$name] as $value )
                        $set[] = self::ToValue( $value );
                    $pairs[] = '`' . $name . '` IN ( ' . $set . ' )';
                } else
                    $pairs[] = '`' . $name . "` = " . self::ToValue( $crit[$name] );
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
     * @return type
     */
    public static function Indexes() {
        return array_keys( static::$_Indexes );
    }
    
    /**
     * 
     * @param type $indexName
     * @return type
     */
    public static function Index($indexName) {
        return static::$_Indexes[$indexName];
    }
    
    /**
     * 
     * @param type $keyValue
     * @param type $force
     * @return type
     */
    public static function FetchByPrimary($keyValue, $force = FALSE) {
        $query = 'SELECT * FROM `' . static::TABLE_NAME . '` WHERE `' . static::PRIMARY_KEY . '` = ' . self::ToValue( $keyValue );
        $row = FALSE;
        if ( ( $result = static::$_Connection->query( $query, MYSQLI_USE_RESULT ) ) ) {
            $row = $result->fetch_assoc( );
            $result->close();
        }
        return $row;
    }
    
    /**
     * 
     * @param type $indexName
     * @param array $crit
     * @param type $limit
     * @param type $offset
     * @param type $force
     * @return type
     */
    public static function Fetch( $indexName = 'PRIMARY', Array $crit = [ ], $limit = FALSE, $offset = 0, $force = FALSE ) {
        $query = 'SELECT * FROM `' . static::TABLE_NAME . '` WHERE ' . ( empty( $crit ) ? 1 : implode( ' AND ', self::ToIndexCriteria( $indexName, $crit ) ) );
        if ( $limit )
            $query .= ' LIMIT ' . $limit;
        if ( $offset )
            $query .= ' OFFSET ' . $offset;
        $row = FALSE;
        if ( ( $result = static::$_Connection->query( $query, MYSQLI_USE_RESULT ) ) ) {
            $rows = $result->fetch_all( MYSQLI_ASSOC );
            $result->close();
        }
        return $rows;
    }
    
    /**
     * 
     * @param type $keyValue
     * @param type $fetchRemoved
     * @return boolean
     */
    public static function RemoveByPrimary($keyValue, $fetchRemoved = FALSE) {
        $result = $keyValue;
        if ( $fetchRemoved )
            $result = self::FetchByPrimary( $keyValue );
        $query = 'DELETE FROM `' . static::TABLE_NAME . '` WHERE `' . static::PRIMARY_KEY . '` = ' . self::ToValue( $keyValue );
        if ( ! $result || ! static::$_Connection->query( $query, MYSQLI_STORE_RESULT ) )
            return FALSE;
        return $result;
    }
    
    /**
     * 
     * @param type $indexName
     * @param array $crit
     * @param type $limit
     * @param type $offset
     * @param type $fetchRemoved
     */
    public static function Remove ( $indexName = 'PRIMARY', Array $crit = [ ], $limit = FALSE, $offset = 0, $fetchRemoved = FALSE ) {
        if ( $fetchRemoved ) {
            $result = self::Fetch( $indexName, $crit, $limit, $offset );
        }
        $query = 'DELETE FROM `' . static::TABLE_NAME . '` WHERE ' . ( empty( $crit ) ? 1 : implode( ' AND ', self::ToIndexCriteria( $indexName, $crit ) ) );
        if ( $limit )
            $query .= ' LIMIT ' . $limit;
        if ( $offset )
            $query .= ' OFFSET ' . $offset;
        if ( ! static::$_Connection->query( $query, MYSQLI_STORE_RESULT ) )
            return FALSE;
        return $fetchRemoved ? $result : static::$_Connection->affected_rows;
        
    }
    
    /**
     * 
     * @param array $row
     * @param type $fetchResult
     * @return boolean
     */
    public static function StoreByPrimary( Array & $row, $fetchResult = FALSE ) {
        if ( isset( $row[static::PRIMARY_KEY] ) && ( $keyValue = $row[static::PRIMARY_KEY] ) ) {
            $query = 'UPDATE `' . static::TABLE_NAME . '` SET ' . implode( ', ', self::ToNameValuePairs( $row ) ) . ' WHERE `' . static::PRIMARY_KEY . '` = ' . self::ToValue( $keyValue );
            if ( ! static::$_Connection->query( $query, MYSQLI_STORE_RESULT ) )
                return FALSE;
        } else {
            $query = 'INSERT INTO `' . static::TABLE_NAME . '` ( `' . implode( '`, `', array_keys( $row ) ) . "` ) VALUES ( '" . implode( "', '", array_values( $row ) ) . "' );";
            if ( ! static::$_Connection->query( $query, MYSQLI_STORE_RESULT )
                    || ! ( $keyValue = static::$_Connection->insert_id ) )
                return FALSE;
        }
        
        if ( $fetchResult )
            $row = self::FetchByPrimary( $keyValue );
        return $keyValue;
    }
    
    /**
     * 
     * @param type $indexName
     * @param array $row
     * @param type $fetchResult
     */
    public static function Store ( Array & $rows, $indexName = 'PRIMARY', $fetchResult = FALSE ) {
        $updateQuery = [ 'UPDATE `' . static::TABLE_NAME . '` SET ', & $pairs, ' WHERE `' . static::PRIMARY_KEY . '` = ', & $keyValue ];
        $insertQuery = [ 'INSERT INTO `' . static::TABLE_NAME . '` ( ' , & $names, ' ) VALUES ( ', & $values, ' );' ];
        $primaryValues = [ ];
//        print_r( $rows );
        foreach ( $rows as $row )
        {
            if ( isset( $row[static::PRIMARY_KEY][0] ) && ( $keyValue = $row[static::PRIMARY_KEY][0] ) ) {
                $pairs = implode( ', ', self::ToNameValuePairs( $row ) );
//                print_r( implode( '', $updateQuery ) );
                if ( ! static::$_Connection->query( implode( '', $updateQuery ), MYSQLI_STORE_RESULT ) )
                    continue;
                $primaryValues[] = $keyValue;
            } else {
                $names = implode( ', ', array_keys( $row ) );
                $values = implode( ', ', self::ToValueList( $row ) );
//                print_r( implode( '', $insertQuery ) );
                if ( ! static::$_Connection->query( implode( '', $insertQuery ), MYSQLI_STORE_RESULT )
                        || ! ( $keyValue = static::$_Connection->insert_id ) )
                    continue;
                $primaryValues[] = $keyValue;
            }
        }
//        print_r( $primaryValues );
        
        if ( $fetchResult ) {
            $query = 'SELECT * FROM `' . static::TABLE_NAME . '` WHERE ' . implode( ' AND ', self::ToIndexCriteria( 'PRIMARY', $crit ) );
            if ( ( $result = static::$_Connection->query( $query, MYSQLI_USE_RESULT ) ) ) {
                $rows = $result->fetch_all( MYSQLI_ASSOC );
                $result->close();
            }
        }
        return $primaryValues;
    }
    
}
