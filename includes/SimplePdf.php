<?php
/**
 * Générateur PDF minimal (texte) pour PLANIPRO — sans dépendance externe.
 */
class SimplePdf
{
    private array $lines = [];
    private string $title;

    public function __construct(string $title = 'Rapport')
    {
        $this->title = $title;
    }

    public function addTitle(string $text): void
    {
        $this->lines[] = ['t' => 'title', 'v' => $text];
    }

    public function addText(string $text): void
    {
        $this->lines[] = ['t' => 'text', 'v' => $text];
    }

    public function addTable(array $headers, array $rows): void
    {
        $this->lines[] = ['t' => 'table', 'h' => $headers, 'r' => $rows];
    }

    public function output(string $filename = 'rapport.pdf'): void
    {
        // Export HTML imprimable (navigateur → Enregistrer en PDF)
        header('Content-Type: text/html; charset=utf-8');
        echo '<!DOCTYPE html><html lang="fr"><head><meta charset="UTF-8"><title>' . htmlspecialchars($this->title) . '</title>';
        echo '<style>
            body{font-family:DejaVu Sans,Arial,sans-serif;margin:24px;color:#111}
            h1{font-size:20px;margin:0 0 8px}
            .meta{color:#555;margin-bottom:20px;font-size:13px}
            table{width:100%;border-collapse:collapse;font-size:12px;margin-top:12px}
            th,td{border:1px solid #ccc;padding:6px 8px;text-align:left;vertical-align:top}
            th{background:#f0f4f8}
            .toolbar{margin-bottom:16px}
            @media print{.toolbar{display:none}}
        </style></head><body>';
        echo '<div class="toolbar"><button onclick="window.print()">Imprimer / Enregistrer en PDF</button></div>';
        echo '<h1>' . htmlspecialchars($this->title) . '</h1>';
        echo '<div class="meta">Généré le ' . date('d/m/Y H:i') . ' — PLANIPRO</div>';

        foreach ($this->lines as $line) {
            if ($line['t'] === 'title') {
                echo '<h2 style="font-size:15px;margin:18px 0 6px">' . htmlspecialchars($line['v']) . '</h2>';
            } elseif ($line['t'] === 'text') {
                echo '<p>' . htmlspecialchars($line['v']) . '</p>';
            } elseif ($line['t'] === 'table') {
                echo '<table><thead><tr>';
                foreach ($line['h'] as $h) {
                    echo '<th>' . htmlspecialchars($h) . '</th>';
                }
                echo '</tr></thead><tbody>';
                foreach ($line['r'] as $row) {
                    echo '<tr>';
                    foreach ($row as $cell) {
                        echo '<td>' . htmlspecialchars((string) $cell) . '</td>';
                    }
                    echo '</tr>';
                }
                echo '</tbody></table>';
            }
        }
        echo '<script>window.onload=function(){setTimeout(function(){window.print()},300)}</script>';
        echo '</body></html>';
        exit;
    }
}
