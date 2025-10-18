<?php
session_start();
// Garanta que os caminhos para autoload e conexao estejam corretos
require_once '../vendor/autoload.php';
require_once '../conexao.php';
include '../includes/sessao.php';
include '../includes/timezone.php';

use PhpOffice\PhpSpreadsheet\Spreadsheet;
use PhpOffice\PhpSpreadsheet\Writer\Xlsx;
// --- NOVO: Inclusão de classes de Estilo ---
use PhpOffice\PhpSpreadsheet\Style\Fill;
use PhpOffice\PhpSpreadsheet\Style\Border;
use PhpOffice\PhpSpreadsheet\Style\Alignment;

/**
 * Função auxiliar para adicionar cláusulas IN na query (essencial para os filtros)
 * @param string $field O campo do banco de dados (ex: 'pf.id_prof').
 * @param array $values Os valores a serem incluídos na cláusula IN.
 * @param array &$conditions Referência ao array de condições da query.
 * @param array &$parameters Referência ao array de parâmetros da query.
 */
function addInCondition(string $field, array $values, array &$conditions, array &$parameters): void
{
    if (!empty($values) && (!isset($values[0]) || $values[0] !== 'todos')) {
        $placeholders = implode(',', array_fill(0, count($values), '?'));
        $conditions[] = "$field IN ($placeholders)";
        $parameters = array_merge($parameters, $values);
    }
}

// --- Constrói a Query de forma segura usando os parâmetros GET ---
$conditions = [];
$parameters = [];
$filename = "relatorio_reduzido_formatado.xlsx";

if (!empty($_GET['nomepac'])) {
    $conditions[] = 'tg.paciente LIKE ?';
    $parameters[] = '%' . $_GET['nomepac'] . '%';
}

if (!empty($_GET['nomeresp'])) {
    $conditions[] = 'tg.responsavel_f LIKE ?';
    $parameters[] = '%' . $_GET['nomeresp'] . '%';
}

addInCondition('pf.id_prof', $_GET['idprof'] ?? [], $conditions, $parameters);
addInCondition('tg.tipopag', $_GET['tipopag'] ?? [], $conditions, $parameters);
addInCondition('tg.modalidadepag', $_GET['modalidaderef'] ?? [], $conditions, $parameters);

if (!empty($_GET['date_start']) && !empty($_GET['date_end'])) {
    $conditions[] = 'DATE(tg.datacad) BETWEEN ? AND ?';
    $date_start = DateTime::createFromFormat('d/m/Y', $_GET['date_start']);
    $date_end = DateTime::createFromFormat('d/m/Y', $_GET['date_end']);
    if ($date_start && $date_end) {
        $parameters[] = $date_start->format('Y-m-d');
        $parameters[] = $date_end->format('Y-m-d');
    }
}

// Query SQL base selecionando apenas os campos necessários
$sql = "SELECT tg.paciente, tg.responsavel_f, tg.datacad, pf.profissional, pf.porcento, tg.valorpag, tg.modalidadepag
        FROM gtoken as tg
        LEFT JOIN profissionais as pf on tg.id_prof = pf.id_prof";

if (!empty($conditions)) {
    $sql .= " WHERE " . implode(" AND ", $conditions);
}
$sql .= " ORDER BY tg.datacad DESC, tg.paciente ASC";

$data = [];
$totals = ['repasse_profissional' => 0]; // Inicializa o total
try {
    $conn = $pdo->open();
    $stmt = $conn->prepare($sql);
    $stmt->execute($parameters);

    while ($row = $stmt->fetch(PDO::FETCH_ASSOC)) {
        // Lógica de cálculo do repasse (copiada de seus outros scripts)
        $porcento = floatval($row['porcento']);
        $valorpag = floatval($row['valorpag']);
        // Adicione aqui as mesmas regras de switch/case se a porcentagem variar por modalidade
        switch ($row['modalidadepag']) {
            case 'Avaliação F':
                $porcento = 80;
                break;
            case 'Avaliação N':
                $porcento = 75;
                break;
            case 'Visita E':
                $porcento = 80;
                break;
            case 'Proase':
            case 'Proase Av':
                $porcento = 60;
                break;
        }

        $repasse_prof = ($porcento * $valorpag / 100);

        $data[] = [
            'paciente'        => $row['paciente'],
            'responsavel_f'   => $row['responsavel_f'] ?? '',
            'data_pagamento'  => date_format(date_create($row['datacad']), 'd/m/Y'),
            'profissional'    => $row['profissional'],
            'repasse_profissional' => $repasse_prof,
        ];

        $totals['repasse_profissional'] += $repasse_prof;
    }

    $pdo->closeConection();
} catch (PDOException $e) {
    die("Erro ao gerar o relatório: " . $e->getMessage());
}

if (empty($data)) {
    echo "Nenhum dado encontrado para os filtros informados.";
    exit;
}

$spreadsheet = new Spreadsheet();
$sheet = $spreadsheet->getActiveSheet()->setTitle("Relatório Reduzido");

// --- Cabeçalhos ---
$headers = [
    "Nº",
    "Nome Paciente",
    "Responsável Financeiro",
    "Data Pag.",
    "Profissional",
    "Repasse Prof."
];

$col = "A";
foreach ($headers as $h) {
    $sheet->setCellValue($col . "1", $h);
    $spreadsheet->getActiveSheet()->getColumnDimension($col)->setAutoSize(true);
    $col++;
}

// --- Dados ---
$rowCount = 2;
foreach ($data as $index => $row) {
    $sheet->setCellValue("A" . $rowCount, $index + 1);
    $sheet->setCellValue("B" . $rowCount, $row["paciente"]);
    $sheet->setCellValue("C" . $rowCount, $row["responsavel_f"]);
    $sheet->setCellValue("D" . $rowCount, $row["data_pagamento"]);
    $sheet->setCellValue("E" . $rowCount, $row["profissional"]);
    $sheet->setCellValue("F" . $rowCount, $row["repasse_profissional"]);
    $rowCount++;
}

// --- Totais ---
$sheet->setCellValue("E" . $rowCount, "TOTAIS:");
$sheet->setCellValue("F" . $rowCount, $totals["repasse_profissional"] ?? 0);
$sheet->getStyle("E" . $rowCount . ":F" . $rowCount)->getFont()->setBold(true);

// --- Formatação de moeda ---
$sheet->getStyle("F2:F" . $rowCount)->getNumberFormat()->setFormatCode('"R$ "#,##0.00');


// --- NOVO: Seção de Estilização ---

// Estilo do Cabeçalho (Fundo vermelho, fonte branca, negrito e centralizado)
$headerStyle = [
    'font' => [
        'bold' => true,
        'color' => ['argb' => 'FFFFFFFF'],
    ],
    'alignment' => [
        'horizontal' => Alignment::HORIZONTAL_CENTER,
        'vertical' => Alignment::VERTICAL_CENTER,
    ],
    'fill' => [
        'fillType' => Fill::FILL_SOLID,
        'startColor' => ['argb' => 'C00000'],
    ],
];
$sheet->getStyle('A1:F1')->applyFromArray($headerStyle);


// Estilo de Bordas para toda a tabela
$lastRow = $rowCount;
$borderStyle = [
    'borders' => [
        'allBorders' => [
            'borderStyle' => Border::BORDER_THIN,
            'color' => ['argb' => '00000000'],
        ],
    ],
];
$sheet->getStyle('A1:F' . $lastRow)->applyFromArray($borderStyle);

// Alinhamento da célula "TOTAIS" para a direita
$sheet->getStyle('E' . $lastRow)->getAlignment()->setHorizontal(Alignment::HORIZONTAL_RIGHT);

// --- Fim da Seção de Estilização ---


if (ob_get_length()) ob_end_clean();

// Download
header("Content-Type: application/vnd.openxmlformats-officedocument.spreadsheetml.sheet");
header("Content-Disposition: attachment; filename=\"$filename\"");
header("Cache-Control: max-age=0");

$writer = new Xlsx($spreadsheet);
$writer->save("php://output");
exit;
