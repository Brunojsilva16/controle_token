<?php
$title = 'Controle de Tokens'; // Título mais apropriado
// Inclui o arquivo de conexão com o banco de dados.
// Certifique-se que o caminho está correto.
include_once('conexao.php');
?>
<!DOCTYPE html>
<html lang="pt-br">

<head>
    <meta charset="UTF-8" />
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title><?php echo $title; ?></title>

    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.css">
    <style>
        /* Estilos básicos para melhor visualização */
        body {
            padding: 20px;
        }
        .table-actions {
            white-space: nowrap;
        }
        .table-actions .btn {
            margin-right: 5px;
        }
    </style>
</head>

<body>

    <div class="container">
        <div class="d-flex justify-content-between align-items-center mb-3">
            <h1><?php echo $title; ?></h1>
            <button type="button" class="btn btn-primary" data-bs-toggle="modal" data-bs-target="#addModal">
                <i class="bi bi-plus-circle"></i> Novo Lançamento
            </button>
        </div>

        <div class="table-responsive">
            <table class="table table-striped table-bordered">
                <thead class="table-dark">
                    <tr>
                        <th>Paciente</th>
                        <th>CPF</th>
                        <th>Valor</th>
                        <th>Profissional</th>
                        <th>Data Cad.</th>
                        <th class="text-center">Ações</th>
                    </tr>
                </thead>
                <tbody>
                    <?php
                    // Bloco PHP para buscar os dados da tabela gtoken e exibi-los
                    try {
                        $conn = $pdo->open();
                        // Query para selecionar os dados, juntando com a tabela de profissionais para pegar o nome
                        $stmt = $conn->prepare("
                            SELECT g.id_token, g.paciente, g.cpf, g.valorpag, g.datacad, p.profissional 
                            FROM gtoken g
                            JOIN profissionais p ON g.id_prof = p.id_prof
                            ORDER BY g.id_token DESC
                        ");
                        $stmt->execute();

                        // Loop para criar uma linha da tabela para cada registro encontrado
                        foreach ($stmt as $row) {
                            echo "
                                <tr>
                                    <td>" . htmlspecialchars($row['paciente']) . "</td>
                                    <td>" . htmlspecialchars($row['cpf']) . "</td>
                                    <td>R$ " . number_format($row['valorpag'], 2, ',', '.') . "</td>
                                    <td>" . htmlspecialchars($row['profissional']) . "</td>
                                    <td>" . date('d/m/Y H:i', strtotime($row['datacad'])) . "</td>
                                    <td class='text-center table-actions'>
                                        <button class='btn btn-sm btn-warning etoken' data-id='" . $row['id_token'] . "'>
                                            <i class='bi bi-pencil-square'></i> Editar
                                        </button>
                                        <button class='btn btn-sm btn-danger deletec' data-id='" . $row['id_token'] . "'>
                                            <i class='bi bi-trash'></i> Excluir
                                        </button>
                                    </td>
                                </tr>
                            ";
                        }
                    } catch (PDOException $e) {
                        echo "<tr><td colspan='6' class='text-center text-danger'>Erro ao conectar ao banco de dados: " . $e->getMessage() . "</td></tr>";
                    }
                    $pdo->closeConection();
                    ?>
                </tbody>
            </table>
        </div>

    </div>

    <div class="modal fade" id="addModal" tabindex="-1" aria-labelledby="addModalLabel" aria-hidden="true">
        <div class="modal-dialog">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title" id="addModalLabel">Adicionar Novo Lançamento</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                </div>
                <div class="modal-body">
                    <form id="frmValidar" method="POST">
                        <div class="mb-3">
                            <label for="nomepaciente" class="form-label">Nome do Paciente</label>
                            <input type="text" class="form-control" id="nomepaciente" name="nomepaciente" required>
                        </div>
                        <div class="mb-3">
                            <label for="cpf" class="form-label">CPF</label>
                            <input type="text" class="form-control" id="cpf" name="cpf">
                        </div>
                        <div class="mb-3">
                            <label for="v_negociavel" class="form-label">Valor (R$)</label>
                            <input type="text" class="form-control" id="v_negociavel" name="v_negociavel" placeholder="150,00" required>
                        </div>
                        
                        <div class="mb-3">
                            <label for="profSelect" class="form-label">Profissional</label>
                            <select class="form-select" id="profSelect" name="profSelect" required>
                                <option value="">Selecione...</option>
                                </select>
                        </div>

                         <div class="mb-3">
                            <label for="userSelect" class="form-label">Usuário</label>
                             <select class="form-select" id="userSelect" name="userSelect" required>
                                 <option value="">Selecione...</option>
                                 </select>
                        </div>
                        
                        <input type="hidden" name="datac[]" value="<?php echo date('Y-m-d'); ?>">
                        <input type="hidden" name="modalidadeRadio" value="Presencial"> <div class="modal-footer">
                            <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Fechar</button>
                            <button type="submit" class="btn btn-primary" id="sbmGerar">Gerar Token</button>
                        </div>
                    </form>
                </div>
            </div>
        </div>
    </div>


    <div class="modal fade" id="edittModal" tabindex="-1" aria-labelledby="editModalLabel" aria-hidden="true">
        <div class="modal-dialog">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title" id="editModalLabel">Editar Lançamento</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                </div>
                <div class="modal-body">
                    <form id="editForm" method="POST">
                        <input type="hidden" id="edit_id" name="id">
                        <input type="hidden" id="edit_idpag" name="idpag" value="1"> <input type="hidden" id="edit_idtokk" name="idtokk">

                        <div class="mb-3">
                            <label for="edit_paciente" class="form-label">Nome do Paciente</label>
                            <input type="text" class="form-control" id="edit_paciente" name="paciente" required>
                        </div>
                        <div class="modal-footer">
                             <div class="carregando"></div>
                             <div class="resultadoLoading"></div>
                            <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Fechar</button>
                            <button type="submit" class="btn btn-success">Salvar Alterações</button>
                        </div>
                    </form>
                </div>
            </div>
        </div>
    </div>


    <div class="modal fade" id="deleteModal" tabindex="-1" aria-labelledby="deleteModalLabel" aria-hidden="true">
        <div class="modal-dialog">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title" id="deleteModalLabel">Confirmar Exclusão</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                </div>
                <div class="modal-body">
                    <p>Você tem certeza que deseja excluir este registro?</p>
                    <form id="deleteForm" method="POST">
                        <input type="hidden" id="idpac" name="iddel">
                        <input type="hidden" name="idpag" value="1"> </form>
                </div>
                <div class="modal-footer">
                    <div class="carregando"></div>
                    <div class="resultadoLoading"></div>
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancelar</button>
                    <button type="submit" form="deleteForm" class="btn btn-danger">Excluir</button>
                </div>
            </div>
        </div>
    </div>

    <script src="https://ajax.googleapis.com/ajax/libs/jquery/3.7.1/jquery.min.js"></script>
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"></script>
    <script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>

    <script src="./sqls/sqls_insertv3.js"></script>
    <script src="./sqls/sqls_edit.js"></script>
    <script src="./sqls/sqls_delete.js"></script>

    <script>
    // Função de exemplo para recarregar a página. Seus scripts 'sqls_delete.js' e 'sqls_edit.js' a utilizam.
    function refresRoload() {
        location.reload();
    }
    
    // Função de exemplo. Você precisa criar a lógica real para ela.
    function listarUsuarios(page){
        console.log("Listar usuários da página: ", page);
        location.reload();
    }
    
    // Função de exemplo. Você precisa criar a lógica real para ela.
    function seach_token(tokenId) {
        console.log("Buscar token: ", tokenId);
        location.reload();
    }

    // Você precisa criar esta função, que é chamada no 'sqls_edit.js'
    // Ela deve buscar os dados do registro via AJAX e preencher o formulário de edição.
    function getEditlsT(id, action) {
        console.log(`Buscando dados para editar. ID: ${id}, Ação: ${action}`);
        // Exemplo de como preencher o formulário (os dados viriam de um AJAX call)
        // Aqui você faria uma requisição ao servidor para pegar os dados do ID específico
        // e preencher os campos do modal '#edittModal'
        $('#edit_id').val(id);
        $('#edit_paciente').val('Nome do Paciente de Teste'); // Substituir com dado real
        $('#edit_idtokk').val('token_exemplo'); // Substituir com dado real
    }
    </script>
</body>

</html>