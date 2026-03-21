<?php


function detecterImprimante()
{
    $output = shell_exec('powershell -command "Get-Printer | Where-Object {$_.Shared -eq $true} | Select-Object -ExpandProperty ShareName"');
    $lignes = explode("\n", $output);
    foreach ($lignes as $ligne) {
        $ligne = trim($ligne);
        if (!empty($ligne)) {
            return $ligne; // Retourne le premier nom de partage trouvé
        }
    }
    return null;
}

define('NOM_IMPRIMANTE', detecterImprimante());
