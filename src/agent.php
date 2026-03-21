<?php

header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Methods: POST, OPTIONS');
header('Access-Control-Allow-Headers: Content-Type');
header('Content-Type: application/json');

if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') {
    http_response_code(200);
    exit;
}

require 'vendor/autoload.php';
require 'config.php';

use Mike42\Escpos\Printer;
use Mike42\Escpos\PrintConnectors\WindowsPrintConnector;
use Mike42\Escpos\PrintConnectors\FilePrintConnector;
use Mike42\Escpos\EscposImage;

// Aligner deux textes sur 32 caractères
function ligne($gauche, $droite, $largeur = 32)
{
    $gauche = substr($gauche, 0, $largeur - strlen($droite) - 1);
    $espace = $largeur - strlen($gauche) - strlen($droite);
    return $gauche . str_repeat(' ', max(1, $espace)) . $droite . "\n";
}

// Découper un texte long proprement
function decouper($texte, $max = 32)
{
    return wordwrap($texte, $max, "\n", true);
}

function imprimerUnTicket(Printer $p, array $d, bool $copieClient = false)
{

    // ── LOGO ─────────────────────────────────
    if (!empty($d['logo'])) {
        $logoData = base64_decode(preg_replace('#^data:image/\w+;base64,#i', '', $d['logo']));
        $tmpFile  = tempnam(sys_get_temp_dir(), 'logo_') . '.png';
        file_put_contents($tmpFile, $logoData);
        try {
            $image = EscposImage::load($tmpFile, false);
            $p->setJustification(Printer::JUSTIFY_CENTER);
            $p->bitImage($image);
            $p->feed(1);
        } catch (Exception $e) {
            // Continue sans logo
        }
        unlink($tmpFile);
    }

    // ── EN-TÊTE ──────────────────────────────
    $p->setJustification(Printer::JUSTIFY_CENTER);

    if ($copieClient) {
        $p->text("------ COPIE CLIENT ------\n");
        $p->feed(1);
    }

    $p->setTextSize(2, 2);
    $p->text(strtoupper($d['commune']) . "\n");
    $p->setTextSize(1, 1);
    $p->feed(1);
    $p->text($d['arrondissement'] . "\n");
    $p->text("Poste : " . $d['site'] . "\n");
    $p->feed(1);

    // ── NUMÉRO + DATE sur la même ligne ──────
    $p->setJustification(Printer::JUSTIFY_CENTER);
    $p->setTextSize(1, 1);
    $p->text("N°");
    $p->feed(1);

    $p->setTextSize(2, 2);
    $p->text($d['code']);
    $p->feed(2);

    $p->setTextSize(1, 1);
    $p->text(ligne("Date", $d['date']));
    $p->text("--------------------------------\n");
    //$p->feed(1);

    // ── INFOS CLIENT ─────────────────────────
    $p->text(ligne("Client", $d['client']));
    $p->text(ligne("Tel", $d['telephone']));

    if (!empty($d['email'])) {
        $p->text(ligne("Email", $d['email']));
    }

    if (!empty($d['contrat_code'])) {
        $p->text(ligne("Contrat", $d['contrat_code']));
    }

    //$p->feed(1);
    $p->text("--------------------------------\n");
    //$p->feed(1);

    // ── DÉTAILS STYLE "Item 1 / 1 x 25.00" ──
    foreach ($d['details'] as $detail) {

        // Nom du service (peut être long, retour à la ligne)
        $lignesService = explode("\n", decouper($detail['service'], 32));
        foreach ($lignesService as $ls) {
            $p->text($ls . "\n");
        }

        // Ligne secondaire grisée : qte x pu = montant (en petits caractères)
        $sousLigne = $detail['qte'] . " x " . $detail['pu'] . " FCFA";
        $p->text(ligne($sousLigne, $detail['montant'] . " FCFA"));
        $p->text("--------------------------------\n");
        //$p->feed(1);
    }

    //$p->text("--------------------------------\n");
    //$p->feed(1);

    // ── RÉCAPITULATIF ────────────────────────
    $p->text(ligne("Montant total", $d['total'] . " FCFA"));
    $p->text(ligne("Montant remis", $d['montant_remis'] ?? "0 FCFA"));
    $p->text(ligne("Reliquat",      $d['reliquat']      ?? "0 FCFA"));
    $p->text(ligne("Paiement",      $d['mode_paiement']));
    $p->feed(1);

    // ── TOTAL EN GRAS ────────────────────────
    // $p->setTextSize(1, 2);
    // $p->setJustification(Printer::JUSTIFY_RIGHT);
    // $p->text($d['total'] . " FCFA\n");
    // $p->setTextSize(1, 1);
    // $p->feed(1);

    // ── MONTANT EN LETTRES ───────────────────
    $p->setJustification(Printer::JUSTIFY_CENTER);
    $p->text("--------------------------------\n");
    $lettres = explode("\n", decouper("Arrêté la présente quittance à la somme de : " . $d['total_lettres'] . " francs CFA", 32));
    foreach ($lettres as $l) {
        $p->text($l . "\n");
    }
    //$p->feed(1);

    // ── OBSERVATION ──────────────────────────
    if (!empty($d['observation'])) {
        $p->text("--------------------------------\n");
        $obs = explode("\n", decouper("Obs : " . $d['observation'], 32));
        foreach ($obs as $o) {
            $p->text($o . "\n");
        }
        $p->feed(1);
    }

    // ── CAISSIER ─────────────────────────────
    $p->text("--------------------------------\n");
    $p->setJustification(Printer::JUSTIFY_RIGHT);
    $p->text("Le caissier\n");
    $p->text($d['caissier'] . "\n");
    //$p->feed(1);

    // // ── TEXTE LÉGAL (Lorem Ipsum) ─────────────
    // $p->setJustification(Printer::JUSTIFY_CENTER);
    // $p->text("--------------------------------\n");
    // $texteLegal = "Lorem ipsum dolor sit amet, consectetur adipiscing elit. Sed do eiusmod tempor incididunt ut labore.";
    // $lignesLegal = explode("\n", decouper($texteLegal, 32));
    // foreach ($lignesLegal as $tl) {
    //     $p->text($tl . "\n");
    // }
    // $p->text("--------------------------------\n");
    //$p->feed(1);

    // ── QR CODE ──────────────────────────────
    $p->setJustification(Printer::JUSTIFY_CENTER);
    $p->qrCode($d['long_code'], Printer::QR_ECLEVEL_M, 6);
    //$p->feed(1);

    // ── PIED DE PAGE ─────────────────────────
    $p->text("Kadoo | " . date('d/m/Y') . " a " . date('H:i:s') . "\n");
    //$p->text("(" . $d['caissier'] . ")\n");

    // ── COUPE ────────────────────────────────
    $p->feed(2);
    $p->cut(Printer::CUT_PARTIAL, 2);
}

// ── MAIN ─────────────────────────────────────

$donnees = json_decode(file_get_contents('php://input'), true);

if (!$donnees) {
    echo json_encode(['ok' => false, 'message' => 'Aucune donnée reçue']);
    exit;
}

try {
    if (PHP_OS_FAMILY === 'Windows') {
        if (!NOM_IMPRIMANTE) {
            echo json_encode([
                'ok'      => false,
                'message' => 'Aucune imprimante Epson détectée. Vérifiez que l\'imprimante est branchée et installée.'
            ]);
            exit;
        }
        $connector = new WindowsPrintConnector(NOM_IMPRIMANTE);
    } else {
        $connector = new FilePrintConnector('/dev/usb/lp0');
    }

    $p = new Printer($connector);

    imprimerUnTicket($p, $donnees, false);
    imprimerUnTicket($p, $donnees, true);

    $p->close();

    echo json_encode(['ok' => true]);
} catch (Exception $e) {
    echo json_encode(['ok' => false, 'message' => $e->getMessage()]);
}
