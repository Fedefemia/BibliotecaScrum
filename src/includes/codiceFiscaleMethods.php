<?php
/**
 * @param $cf
 * @return array
 */
function extractFromCodiceFiscale($cf) : array {
    $codiceFiscale = strtoupper($cf);
    if (strlen($codiceFiscale) !== 16) {
        return [];
    }
    $cognome = substr($codiceFiscale, 0, 3);
    $nome    = substr($codiceFiscale, 3, 3);
    $anno    = substr($codiceFiscale, 6, 2);
    $mese    = substr($codiceFiscale, 8, 1);
    $giorno  = substr($codiceFiscale, 9, 2);
    $comune  = substr($codiceFiscale, 11, 4);
    $mesi = [
        "A" => 1, "B" => 2, "C" => 3, "D" => 4,
        "E" => 5, "H" => 6, "L" => 7, "M" => 8,
        "P" => 9, "R" => 10, "S" => 11, "T" => 12
    ];
    $meseNum = $mesi[$mese] ?? null;
    $giornoNum = intval($giorno);
    if ($giornoNum > 40) {
        $sesso = "F";
        $giornoNum -= 40;
    } else {
        $sesso = "M";
    }
    $annoInt = intval($anno);
    $annoCompleto = ($annoInt >= 0 && $annoInt <= intval(date("y")))
        ? 2000 + $annoInt
        : 1900 + $annoInt;

    return [
        "cognome" => $cognome,
        "nome"    => $nome,
        "data_nascita" => sprintf("%04d-%02d-%02d", $annoCompleto, $meseNum, $giornoNum),
        "sesso"        => $sesso,
        "comune_nascita" => $comune
    ];
}

/**
 * @param string $nome
 * @param string $cognome
 * @param string $dataNascita
 * @param string $sesso
 * @param string $codiceComune
 * @return string
 */
function generateCodiceFiscale(string $nome, string $cognome, string $dataNascita, string $sesso, string $codiceComune) : string {
    //Definizione funzioni interne
    /**
     * @param string $str
     * @param bool $isCognome
     * @return string
     */
    function codificaCognomeONome(string $str, bool $isCognome) : string {
        $vocali = ["A","E","I","O","U"];
        $cons = [];
        $vocs = [];
        for ($i = 0; $i < strlen($str); $i++) {
            $c = $str[$i];
            if (ctype_alpha($c)) {
                if (in_array($c, $vocali)) $vocs[] = $c;
                else $cons[] = $c;
            }
        }
        if (!$isCognome && count($cons) >= 4) {
            return $cons[0] . $cons[2] . $cons[3];
        }
        $cod = array_merge($cons, $vocs);
        return str_pad(implode("", array_slice($cod, 0, 3)), 3, "X");
    }

    /**
     * @param string $codice15
     * @return string
     */
    function calcolaCheckCF(string $codice15) : string {
        $mapDispari = [
            '0'=>1,'1'=>0,'2'=>5,'3'=>7,'4'=>9,'5'=>13,'6'=>15,'7'=>17,'8'=>19,'9'=>21,
            'A'=>1,'B'=>0,'C'=>5,'D'=>7,'E'=>9,'F'=>13,'G'=>15,'H'=>17,'I'=>19,'J'=>21,
            'K'=>2,'L'=>4,'M'=>18,'N'=>20,'O'=>11,'P'=>3,'Q'=>6,'R'=>8,'S'=>12,'T'=>14,
            'U'=>16,'V'=>10,'W'=>22,'X'=>25,'Y'=>24,'Z'=>23
        ];
        $mapPari = [
            '0'=>0,'1'=>1,'2'=>2,'3'=>3,'4'=>4,'5'=>5,'6'=>6,'7'=>7,'8'=>8,'9'=>9,
            'A'=>0,'B'=>1,'C'=>2,'D'=>3,'E'=>4,'F'=>5,'G'=>6,'H'=>7,'I'=>8,'J'=>9,
            'K'=>10,'L'=>11,'M'=>12,'N'=>13,'O'=>14,'P'=>15,'Q'=>16,'R'=>17,'S'=>18,'T'=>19,
            'U'=>20,'V'=>21,'W'=>22,'X'=>23,'Y'=>24,'Z'=>25
        ];
        $alfabeto = "ABCDEFGHIJKLMNOPQRSTUVWXYZ";
        $somma = 0;
        for ($i=0; $i<15; $i++) {
            $c = $codice15[$i];
            if ($i % 2 == 0) {
                $somma += $mapDispari[$c];
            } else {
                $somma += $mapPari[$c];
            }
        }
        $resto = $somma % 26;
        return $alfabeto[$resto];
    }

    $nome = strtoupper($nome);
    $cognome = strtoupper($cognome);
    $sesso = strtoupper($sesso);
    $codiceComune = strtoupper($codiceComune);
    $codiceCognome = codificaCognomeONome($cognome, true);
    $codiceNome = codificaCognomeONome($nome, false);
    [$anno, $mese, $giorno] = explode("-", $dataNascita);
    $anno = substr($anno, -2);
    $mesi = [
        1 => "A", 2 => "B", 3 => "C", 4 => "D",
        5 => "E", 6 => "H", 7 => "L", 8 => "M",
        9 => "P", 10 => "R", 11 => "S", 12 => "T"
    ];
    $meseLettera = $mesi[intval($mese)] ?? "A";
    $giornoNum = intval($giorno);
    if ($sesso === "F") $giornoNum += 40;
    $giorno = str_pad($giornoNum, 2, "0", STR_PAD_LEFT);
    $parziale = $codiceCognome . $codiceNome . $anno . $meseLettera . $giorno . $codiceComune;
    $check = calcolaCheckCF($parziale);
    return $parziale.$check;
}