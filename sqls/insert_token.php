<?php
include_once('../conexao.php');
include '../includes/sessao.php';
include '../includes/timezone.php';

?>
<?php

// $paramArray = array();
$output = array('error' => false);

// if (isset($_POST['nomepaciente'])) {

// "<pre>";
// echo print_r($_POST);
// "</pre>";

// $output['post'] = print_r($_POST);
// exit;

$nomepaciente = $_POST['nomepaciente'];
$cpf = $_POST['cpf'];
$nomeresp = (!empty($_POST['nomeresp'])) ? $_POST['nomeresp'] : NULL;
$respfinanceiro = (!empty($_POST['fresponsavel'])) ? $_POST['fresponsavel'] : NULL;
$profSelect = $_POST['profSelect'];

$pag_resp = (!empty($_POST['pag_resp'])) ? $_POST['pag_resp'] : NULL;
$listbanco = (!empty($_POST['listbanco'])) ? $_POST['listbanco'] : NULL;

$modalidadeRadio = $_POST['modalidadeRadio'];
$v_valor = $_POST['v_negociavel'];
// remover somente manter os números inteiros e divido-los por 100.
$valor = preg_replace('/[^0-9]/', '', $v_valor);
$valor = bcdiv($valor, 100, 2);
$vpag = strtr($valor, ',', '.');


$tipopag = (!empty($_POST['listforma'])) ? $_POST['listforma'] : NULL;
$v = "";
foreach ($_POST['datac'] as $id => $value) {
    if ($value != '') {
        $v .= $value . ", ";
    }
}
$v_datac = rtrim($v, ", ");
$statuspag = 'efetuado';
$obs = (!empty($_POST['obs'])) ? $_POST['obs'] : NULL;
// $exib = $_POST['userExib'];

$setstr = '0123456789';
$tokenNew = substr(str_shuffle($setstr), 0, 2);
$tokenNew .= time();
$tokenNew .= substr(str_shuffle($setstr), 0, 4);

$usuario = $_POST['userSelect'];
$datacad = date("Y-m-d H:i:h");
// $timestamp = strtotime($datacad);

$output['last_token'] = $tokenNew;

try {
    $conn = $pdo->open();

    $sql = "INSERT INTO `gtoken`(`paciente`, `cpf`, `nomeresp`, `responsavel_f`, `pag_resp`, `nome_banco`, `id_prof`, `modalidadepag`, `valorpag`, `tipopag`, `datapag`, `statuspag`, `obs`, `token`, `id_user`, `datacad`)
        VALUES (?,?,?,?,?,?,?,?,?,?,?,?,?,?,?,?)";

    $paramValue = array(
        $nomepaciente,
        $cpf,
        $nomeresp,
        $pag_resp,
        $listbanco,
        $nomeresp,
        $respfinanceiro,
        $profSelect,
        $modalidadeRadio,
        $vpag,
        $tipopag,
        $v_datac,
        $statuspag,
        $obs,
        $tokenNew,
        $usuario,
        $datacad
    );

    $result = $conn->prepare($sql);

    if ($paramValue) {
        foreach ($paramValue as $key => $val) {
            // $type = (is_numeric($val) ? \PDO::PARAM_INT : \PDO::PARAM_STR);
            $result->bindValue($key + 1, $val);
        }
    }

    $result->execute();
    $row_count = $result->rowCount();

    // $row_count = $stmt->rowCount();
    $output['row_count'] = $row_count;

    if ($row_count > 0) {
        $output['last_token'] = $tokenNew;
        $output['message'] = 'Cadastro realizado com sucesso!';
    } else {
        $output['error'] = true;
        $output['message'] = "Problema com a gravação, tente novamente!";
    }
} catch (PDOException $e) {
    $output['error'] = true;
    $output['message'] = "Problema com a conexão: " . $e->getMessage();
    // exit;
}
$pdo->closeConection();

echo json_encode($output);

?>