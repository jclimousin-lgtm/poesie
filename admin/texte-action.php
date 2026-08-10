<?php

declare(strict_types=1);

require __DIR__ . '/_admin.php';

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    header('Location: textes.php');
    exit;
}

$pdo = poesie_db();
$ids = array_values(array_unique(array_filter((array) ($_POST['ids'] ?? []), static fn($v) => is_string($v) && $v !== '')));
$action = (string) ($_POST['action'] ?? '');
$categorieSlug = trim((string) ($_POST['categorie_slug'] ?? ''));

$rapport = [];

if ($ids !== []) {
    $placeholders = implode(',', array_fill(0, count($ids), '?'));

    switch ($action) {
        case 'publier':
        case 'depublier':
            $statut = $action === 'publier' ? 'publie' : 'brouillon';
            $stmt = $pdo->prepare("UPDATE documents SET statut_publication = ? WHERE id_interne IN ({$placeholders})");
            $stmt->execute(array_merge([$statut], $ids));
            $rapport[] = $stmt->rowCount() . ' texte(s) mis a jour (' . $statut . ')';
            break;

        case 'ajouter_categorie':
            if ($categorieSlug !== '') {
                $stmt = $pdo->prepare('REPLACE INTO document_categories (id_interne, categorie_slug) VALUES (?, ?)');
                $n = 0;
                foreach ($ids as $id) {
                    $stmt->execute([$id, $categorieSlug]);
                    $n++;
                }
                $rapport[] = "{$n} affectation(s) ajoutee(s) a {$categorieSlug}";
            }
            break;

        case 'retirer_categorie':
            if ($categorieSlug !== '') {
                $stmt = $pdo->prepare("DELETE FROM document_categories WHERE categorie_slug = ? AND id_interne IN ({$placeholders})");
                $stmt->execute(array_merge([$categorieSlug], $ids));
                $rapport[] = $stmt->rowCount() . " affectation(s) retiree(s) de {$categorieSlug}";
            }
            break;

        case 'supprimer':
            // Protection du corpus fondateur : jamais supprimable depuis cette action,
            // meme si son id_interne a ete coche par erreur dans une selection en lot.
            $stmtFondateur = $pdo->prepare("SELECT id_interne FROM documents WHERE id_interne IN ({$placeholders}) AND origine = 'corpus_fondateur'");
            $stmtFondateur->execute($ids);
            $proteges = $stmtFondateur->fetchAll(PDO::FETCH_COLUMN);
            $suppressibles = array_values(array_diff($ids, $proteges));

            if ($suppressibles !== []) {
                $placeholdersSupp = implode(',', array_fill(0, count($suppressibles), '?'));
                $pdo->prepare("DELETE FROM document_categories WHERE id_interne IN ({$placeholdersSupp})")->execute($suppressibles);
                $pdo->prepare("DELETE FROM document_series WHERE id_interne IN ({$placeholdersSupp})")->execute($suppressibles);
                $pdo->prepare("DELETE FROM documents WHERE id_interne IN ({$placeholdersSupp})")->execute($suppressibles);
                $rapport[] = count($suppressibles) . ' texte(s) supprime(s)';
            }
            if ($proteges !== []) {
                $rapport[] = count($proteges) . ' texte(s) du corpus fondateur ignore(s) (protection permanente) : ' . implode(', ', $proteges);
            }
            break;
    }
}

$message = implode(' — ', $rapport) !== '' ? implode(' — ', $rapport) : 'Aucune action effectuee.';
header('Location: textes.php?msg=' . urlencode($message));
exit;
