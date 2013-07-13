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
 * Description of DAOMySQLGen
 *
 * @author Erich
 */

require_once 'ADAOMySQL.class.php';
require_once 'DAO.interface.php';

class DAOMySQLGen
extends ADAOMySQL
{
    protected static $_Connection = NULL;
    
    public static function Export ( $value )
    {
        if ( $value === NULL || is_scalar( $value ) )
            return json_encode( $value );
        $result = [ ];
        foreach ( $value as $key => $val )
            if ( is_int( $key ) )
                $result[] = self::Export( $val );
            else
                $result[] = json_encode( $key, JSON_OBJECT_AS_ARRAY )  . ' => ' . self::Export( $val );
        return '[' . implode( ', ', $result ) . ']';
    }
    
    public static function GetDatabaseNames ( )
    {
        if ( ! ( $connection = self::$_Connection ) )
            throw new \Exception( 'No database connection' );
        
        $databases = [ ];
        if ( ( $result = $connection->query( 'SHOW TABLES', MYSQLI_USE_RESULT ) )
                && ( $rows = $result->fetch_all( ) ) )
        {
            $result->close( );
            foreach ( $rows as $tinfo )
                $databases[] = $tinfo[0];
        }
        
        return $databases;
    }
    
    public static function GetTableNames ( $databaseName = FALSE )
    {
        if ( ! ( $connection = self::$_Connection ) )
            throw new \Exception( 'No database connection' );
        if ( ! is_string( $databaseName ) )
            $databaseName = defined( $const = get_called_class( ) . '::DATABASE_NAME' ) ? constant ( $const ) : '';
        if ( $databaseName && ! $connection->select_db( $databaseName ) )
            throw new \Exception( 'Could not use database ' . $databaseName );
        
        $tables = [ ];
        if ( ( $result = $connection->query( 'SHOW TABLES', MYSQLI_USE_RESULT ) )
                && ( $rows = $result->fetch_all( ) ) )
        {
            $result->close( );
            foreach ( $rows as $tinfo )
                $tables[] = $tinfo[0];
        }
        
        return $tables;
    }
    
    protected static function GetColumns ( $tableName = FALSE, $databaseName = FALSE )
    {
        if ( ! ( $connection = self::$_Connection ) )
            throw new \Exception( 'No database connection' );
        if ( ! is_string( $databaseName ) )
            $databaseName = defined( $const = get_called_class( ) . '::DATABASE_NAME' ) ? constant ( $const ) : '';
        if ( $databaseName && ! $connection->select_db( $databaseName ) )
            throw new \Exception( 'Could not use database ' . $databaseName );
        if ( ! is_string( $tableName ) )
        {
            if ( ! defined( $const = get_called_class( ) . '::TABLE_NAME' ) )
                throw new \Exception( 'No table name specified for database' . $databaseName );
            $tableName =  constant ( $const );
        }
        if ( ( ! $result = $connection->query( 'DESC `' . $tableName . '`', MYSQLI_USE_RESULT ) ) )
            throw new \Exception( 'No such table ' . $tableName . ' in database ' . $databaseName );
        if ( ! ( $fields = $result->fetch_all( MYSQLI_ASSOC ) ) )
            throw new \Exception( 'Could not read columns of table ' . $tableName . ' in database ' . $databaseName );
        $result->close( );
        
        $columns = [ ];
        foreach ( $fields as $finfo )
        {
            $name = $finfo['Field'];
            $type = $finfo['Type'];
            $index = ! empty( $finfo['Key'] );
            $primary = $unique = FALSE;
            switch ( $finfo['Key'] )
            {
                case 'PRI': $primary = TRUE;
                case 'UNI': $unique = TRUE;
            }
            $length = $decimals = $unsigned = 0;
            $set = NULL;
            $unsigned = FALSE;
            if ( preg_match( "/^(\\w+)(?:\\((\d+(?:,\d+)*)\\)(?:\\s+(unsigned))?|\\(('[^'\\\\]+'(?:,'[^'\\\\]+')*)\\))?$/i", $type, $matches ) )
            {
                if ( ! empty( $matches[1] ) )
                    $type = $matches[1];
                if ( ! empty( $matches[2] ) )
                {
                    $parts = split( ',', $matches[2] );
                    $length = intval( $parts[0] );
                    if ( count( $parts ) > 1 )
                        $decimals = intval( $parts[1] );
                    if ( ! empty( $matches[3] ) )
                        $unsigned = !! $matches[3];
                }
                elseif ( ! empty( $matches[4] ) )
                {
                    $set = [ ];
                    if ( preg_match_all( "/'(?:[^'\\\\]+|\\\\.)*'/", $matches[4], $parts ) )
                    {
                        foreach ( $parts[0] as $part )
                            $set[] = stripslashes( substr( $part, 1, -1 ) );
                    }
                }
            }
            $columns[$name] = [
                'name'      => $name,
                'type'      => $type,
                'length'    => $length,
                'decimals'  => $decimals,
                'unsigned'  => $unsigned,
                'elements'  => $set,
                'null'      => $finfo['Null'] !== 'NO',
                'index'     => $index,
                'primary'   => $primary,
                'unique'    => $unique,
                'auto'      => stripos( $finfo['Extra'], 'AUTO_INCREMENT' ) !== FALSE,
                'default'   => $finfo['Default']
            ];
        }
        
        return $columns;
    }
    
    protected function GetIndexes ( $tableName, $databaseName = FALSE )
    {
        if ( ! ( $connection = self::$_Connection ) )
            throw new \Exception( 'No database connection' );
        if ( ! is_string( $databaseName ) )
            $databaseName = defined( $const = get_called_class( ) . '::DATABASE_NAME' ) ? constant ( $const ) : '';
        if ( $databaseName && ! $connection->select_db( $databaseName ) )
            throw new \Exception( 'Could not use database ' . $databaseName );
        if ( ! is_string( $tableName ) )
        {
            if ( ! defined( $const = get_called_class( ) . '::TABLE_NAME' ) )
                throw new \Exception( 'No table name specified for database' . $databaseName );
            $tableName =  constant ( $const );
        }
        if ( ( ! $result = $connection->query( 'SHOW INDEX FROM `' . $tableName . '`', MYSQLI_USE_RESULT ) ) )
            throw new \Exception( 'No such table ' . $tableName . ' in database ' . $databaseName );
        if ( ! ( $fields = $result->fetch_all( MYSQLI_ASSOC ) ) )
            throw new \Exception( 'Could not read columns of table ' . $tableName . ' in database ' . $databaseName );
        $result->close( );
        
        $indexes = [ ];
        foreach ( $fields as $finfo )
        {
            if ( ! isset( $indexes[$name = $finfo['Key_name']] ) )
                $indexes[$name] = [ ];
            $indexes[$name][$finfo['Seq_in_index'] - 1] = $finfo['Column_name'];
        }
        
        return $indexes;
    }
    
    public static function GenerateDatabaseDao ( $databaseClassName, $databaseName )
    {
        $result = [
            'class ' . $databaseClassName,
            'extends ADAOMySQL',
            '{',
            "\tconst DATABASE_NAME = '{$databaseName}';",
            "\t",
            "\tprotected static \$_Connection = NULL;",
            '}'
        ];
        return implode( PHP_EOL, $result );
    }
    
    public static function GenerateTableDao ( $tableClassName, $tableName, $databaseClassName, $databaseName )
    {
        $columns = self::GetColumns( $tableName, $databaseName );
        $indexes = self::GetIndexes( $tableName, $databaseName );
        
        $result = [
            'class ' . $tableClassName,
            'extends ' . $databaseClassName,
            'implements DAO',
            '{',
            "\tconst TABLE_NAME = '{$tableName}';",
            "\tconst PRIMARY_KEY = '" . ( empty( $indexes['PRIMARY'][0] ) ? NULL : $indexes['PRIMARY'][0] ) . "';",
            "\t",
            "\tprotected static \$_Columns = " . self::Export( $columns, TRUE ) . ';',
            "\tprotected static \$_Indexes = " . self::Export( $indexes, TRUE ) . ';',
            "\tprotected static \$_Cache = [ ];",
            '}'
        ];
        return implode( PHP_EOL, $result );
    }
    
}
