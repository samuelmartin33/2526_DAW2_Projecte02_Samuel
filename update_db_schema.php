<?php
require_once __DIR__ . '/bbdd/conexion.php';

try {
    echo "Updating database schema...\n";

    // 1. Add nombre_cliente_reserva column
    try {
        $conn->exec("ALTER TABLE reservas ADD COLUMN nombre_cliente_reserva VARCHAR(100) DEFAULT NULL");
        echo "- Added column 'nombre_cliente_reserva'\n";
    } catch (PDOException $e) {
        // Ignorar si ya existe
        echo "- Column 'nombre_cliente_reserva' might already exist or error: " . $e->getMessage() . "\n";
    }

    // 2. Add telefono_reserva column
    try {
        $conn->exec("ALTER TABLE reservas ADD COLUMN telefono_reserva VARCHAR(20) DEFAULT NULL");
        echo "- Added column 'telefono_reserva'\n";
    } catch (PDOException $e) {
        // Ignorar si ya existe
        echo "- Column 'telefono_reserva' might already exist or error: " . $e->getMessage() . "\n";
    }

    // 3. Make id_usuario_reserva NULLABLE
    // Nota: Es una FK, así que primero hay que checkear si debemos dropear la foreign key o si ALTER TABLE MODIFY COLUMN funciona con FK.
    // En MySQL, cambiar la columna a NULLABLE no suele requerir borrar la FK, pero sí el MODIFY.
    try {
        $conn->exec("ALTER TABLE reservas MODIFY COLUMN id_usuario_reserva INT NULL");
        echo "- Made 'id_usuario_reserva' NULLABLE\n";
    } catch (PDOException $e) {
         echo "- Error modifying 'id_usuario_reserva': " . $e->getMessage() . "\n";
    }

    echo "Database update completed.\n";

} catch (Exception $e) {
    echo "General Error: " . $e->getMessage() . "\n";
}
