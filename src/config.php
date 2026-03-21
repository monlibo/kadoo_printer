<?php


function detecterImprimante()
{
    // Demander à Windows la liste des imprimantes via PowerShell
    $output = shell_exec('powershell -command "Get-Printer | Select-Object -ExpandProperty Name"');

    // Découper le résultat ligne par ligne
    $lignes = explode("\n", $output);

    foreach ($lignes as $ligne) {
        $ligne = trim($ligne);

        // Chercher une imprimante Epson TM
        if (stripos($ligne, 'EPSON') !== false || stripos($ligne, 'TM') !== false) {
            return $ligne;
        }
    }

    return null;
}
$nomImprimante = detecterImprimante();
error_log("Config - Imprimante : " . $nomImprimante);
define('NOM_IMPRIMANTE', $nomImprimante);
