<?php
// includes/db.php - Funciones de apoyo para consultas a la base de datos

/**
 * Ejecuta una consulta preparada y devuelve el statement.
 *   ejecutar($cx, 'SELECT ... WHERE id = ?', 'i', [$id]);
 * Los valores nunca se concatenan en el SQL (evita inyección SQL).
 */
function ejecutar(mysqli $cx, string $sql, string $tipos = '', array $params = []): mysqli_stmt
{
    $stmt = mysqli_prepare($cx, $sql);
    if ($tipos !== '') {
        mysqli_stmt_bind_param($stmt, $tipos, ...$params);
    }
    mysqli_stmt_execute($stmt);
    return $stmt;
}

/** Ejecuta una consulta y devuelve todas las filas como arreglo asociativo. */
function consultar_filas(mysqli $cx, string $sql, string $tipos = '', array $params = []): array
{
    $resultado = mysqli_stmt_get_result(ejecutar($cx, $sql, $tipos, $params));
    return mysqli_fetch_all($resultado, MYSQLI_ASSOC);
}

/** Ejecuta una consulta y devuelve solo la primera fila (o null). */
function consultar_fila(mysqli $cx, string $sql, string $tipos = '', array $params = []): ?array
{
    $resultado = mysqli_stmt_get_result(ejecutar($cx, $sql, $tipos, $params));
    return mysqli_fetch_assoc($resultado) ?: null;
}

/** Formatea una cantidad como dinero: $1,234.50  /  -$500.00 */
function dinero(float|string|null $monto, string $simbolo = '$'): string
{
    $monto = (float) $monto;
    return ($monto < 0 ? '-' : '') . $simbolo . number_format(abs($monto), 2);
}
