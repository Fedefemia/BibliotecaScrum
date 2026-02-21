<?php

function recuperaBadges($uid, $pdo) {
    $stm = $pdo->prepare("
        SELECT b.*, ub.livello 
        FROM utente_badge ub
        JOIN badge b ON ub.id_badge = b.id_badge
        WHERE ub.codice_alfanumerico = ?
        ORDER BY b.id_badge ASC
    ");
    $stm->execute([$uid]);
    return $stm->fetchAll(PDO::FETCH_ASSOC);
}

function calcolaBadges($uid, $pdo) {
    $user_stats = [];
    $badges_to_display = [];

    // Libri Letti
    $stm = $pdo->prepare("SELECT COUNT(*) FROM prestiti WHERE codice_alfanumerico = ? AND data_restituzione IS NOT NULL");
    $stm->execute([$uid]);
    $user_stats['libri_letti'] = $stm->fetchColumn();

    // Restituzioni Puntuali
    $stm = $pdo->prepare("SELECT COUNT(*) FROM prestiti WHERE codice_alfanumerico = ? AND data_restituzione IS NOT NULL AND data_restituzione <= data_scadenza");
    $stm->execute([$uid]);
    $user_stats['restituzioni_puntuali'] = $stm->fetchColumn();

    // Numero Multe (Qui meno ne hai, meglio è)
    $stm = $pdo->prepare("SELECT COUNT(*) FROM multe m JOIN prestiti p ON m.id_prestito = p.id_prestito WHERE p.codice_alfanumerico = ?");
    $stm->execute([$uid]);
    $user_stats['numero_multe'] = $stm->fetchColumn();

    // Recensioni Scritte
    $stm = $pdo->prepare("SELECT COUNT(*) FROM recensioni WHERE codice_alfanumerico = ?");
    $stm->execute([$uid]);
    $user_stats['recensioni_scritte'] = $stm->fetchColumn();

    // Prestiti Effettuati (Totali)
    $stm = $pdo->prepare("SELECT COUNT(*) FROM prestiti WHERE codice_alfanumerico = ?");
    $stm->execute([$uid]);
    $user_stats['prestiti_effettuati'] = $stm->fetchColumn();

    // --- B. RECUPERO STATO ATTUALE DB ---
    $stm = $pdo->prepare("SELECT id_badge FROM utente_badge WHERE codice_alfanumerico = ?");
    $stm->execute([$uid]);
    $already_unlocked = $stm->fetchAll(PDO::FETCH_COLUMN, 0);

    // --- C. LOGICA DI CONFRONTO E SALVATAGGIO ---
    $stm = $pdo->query("SELECT * FROM badge ORDER BY id_badge ASC");
    $all_badges = $stm->fetchAll(PDO::FETCH_ASSOC);

    $badges_by_type = [];
    foreach ($all_badges as $b) {
        $badges_by_type[$b['tipo']][] = $b;
    }

    foreach ($badges_by_type as $type => $badges_list) {
        // Ordinamento per trovare il massimo raggiungibile
        usort($badges_list, function($a, $b) use ($type) {
            return ($type === 'numero_multe')
                ? $b['target_numerico'] - $a['target_numerico']
                : $a['target_numerico'] - $b['target_numerico'];
        });

        $highest_unlocked = null;
        $next_badge = null;

        foreach ($badges_list as $b) {
            $is_earned = false;
            $currentVal = $user_stats[$type] ?? 0;
            $target = intval($b['target_numerico']);

            // Verifica se i requisiti sono soddisfatti
            if ($type === 'numero_multe') {
                if ($currentVal <= $target) $is_earned = true;
            } else {
                if ($currentVal >= $target) $is_earned = true;
            }

            if ($is_earned) {
                $highest_unlocked = $b;
                $highest_unlocked['is_unlocked'] = true;

                //Inserimento se necessario
                if (!in_array($b['id_badge'], $already_unlocked)) {
                    $ins = $pdo->prepare("INSERT IGNORE INTO utente_badge (id_badge, codice_alfanumerico, livello) VALUES (?, ?, ?)");
                    // Usiamo il nome o il target come "livello" descrittivo se necessario
                    $ins->execute([$b['id_badge'], $uid, $b['nome']]);
                    $already_unlocked[] = $b['id_badge'];
                }
            } else {
                if ($next_badge === null) $next_badge = $b;
            }
        }

        if ($highest_unlocked) {
            $display_item = $highest_unlocked;
            if ($next_badge) $display_item['next_badge'] = $next_badge;
            $badges_to_display[] = $display_item;
        } elseif (!empty($badges_list)) {
            $first = $badges_list[0];
            $first['is_unlocked'] = false;
            $first['next_badge'] = $first;
            $badges_to_display[] = $first;
        }
    }

    return $badges_to_display;
}
