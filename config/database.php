<?php

final class Database
{
    private static ?mysqli $connection = null;

    public static function connection(): mysqli
    {
        if (self::$connection instanceof mysqli) {
            return self::$connection;
        }

        $host = 'localhost';
        $user = 'root';
        $password = '';
        $database = 'wku_admission';

        mysqli_report(MYSQLI_REPORT_ERROR | MYSQLI_REPORT_STRICT);

        self::$connection = new mysqli($host, $user, $password, $database);
        self::$connection->set_charset('utf8mb4');

        return self::$connection;
    }
}
