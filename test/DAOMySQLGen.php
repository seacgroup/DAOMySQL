<!DOCTYPE html>
<html>
    <head>
        <meta http-equiv="Content-Type" content="text/html; charset=UTF-8">
        <title></title>
    </head>
    <body>
        <pre>
<?php
require_once '../lib/DAOMySQLGen.class.php';

DAOMySQLGen::Connect( [ 'user' => 'memory', 'pass' => '5un5h1n3' ] );
$databaseName = 'memory';
$databaseClassName = 'Dao' . ucfirst( strtolower( $databaseName ) );

$tfp = 'test.php';
$fh = fopen( $tfp, 'w' );
fwrite( $fh, '<?php' . PHP_EOL );
fwrite( $fh, DAOMySQLGen::GenerateDatabaseDao( $databaseClassName, $databaseName ) . PHP_EOL );
foreach ( DAOMySQLGen::GetTableNames( $databaseName ) as $tableName )
{
    $tableClassName = 'Dao' . ucfirst( strtolower( $databaseName ) ) . ucfirst( strtolower( $tableName ) );
    fwrite( $fh, DAOMySQLGen::GenerateTableDao( $tableClassName, $tableName, $databaseClassName, $databaseName ) . PHP_EOL );
}
fclose( $fh );
DAOMySQLGen::Close( );
readfile( $tfp );

require_once $tfp;

DaoMemory::Connect( [ 'user' => 'memory', 'pass' => '5un5h1n3' ] );
print_r( DaoMemoryUsers::FetchByPrimary( 1 ) );
print_r( $row = DaoMemoryUsers::StoreByPrimary( [
    "username" => 'test6',
    "userpassword" => 'test6',
    "usernick" => 'test6',
    "userage" => 25,
    "useremail" => 'lia@seachawaii.com',
    "userphone" => '+62 881276096972',
    "userstatus" => 'new',
    "userenabled" => 1,
    "usercreated" => '2013-07-14 00:06:55',
    "usermodified" => '2013-07-14 00:07:06'
] ) );
print_r( $row = DaoMemoryUsers::StoreByPrimary( $row ) );
print_r( DaoMemoryUsers::RemoveByPrimary( $row['userid'] ) );
?>
        </pre>
    </body>
</html>
