<?php
// test_permissions_cli.php
require_once 'includes/permisos.php';

function mockTienePermiso($rol, $modulo)
{
    global $PERMISOS;
    if (!isset($PERMISOS[$rol]))
        return false;
    if (in_array('*', $PERMISOS[$rol]))
        return true;
    return in_array($modulo, $PERMISOS[$rol]);
}

echo "Testing Permissions Logic:\n";
echo "Rol: Gerente\n";
echo "Permiso 'compras': " . (mockTienePermiso('Gerente', 'compras') ? 'YES' : 'NO') . "\n";

echo "Rol: Administrativo\n";
echo "Permiso 'compras': " . (mockTienePermiso('Administrativo', 'compras') ? 'YES' : 'NO') . "\n";
?>