<!DOCTYPE html> 
<html lang="pt-br"> 
<head> 
    <meta charset="utf-8" />
    <meta name="viewport" content="width=device-width, initial-scale=1.0" /> 
    <link href="Crunchyroll.css" rel="stylesheet" />
    <title>Detalhes do Anime</title>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css"> 
</head> 
<body> 
    
    <div class="detalhes-container">
        <?php
        // Conexão com o banco de dados
        require "admin/conexao.php";
        
        // Verificar se o ID foi passado
        if (!isset($_GET['id']) || empty($_GET['id'])) {
            header("Location: index.php");
            exit;
        }
        
        $anime_id = $_GET['id'];
        
        // Buscar dados do anime e seus detalhes
        $sql = "SELECT c.*, i.* 
                FROM carrossel c 
                LEFT JOIN indv_anime i ON c.id = i.id_carrossel 
                WHERE c.id = :id";
        $stmt = $pdo->prepare($sql);
        $stmt->execute([':id' => $anime_id]);
        $anime = $stmt->fetch(PDO::FETCH_ASSOC);
        
        if (!$anime) {
            echo "<p style='color: white; text-align: center; margin-top: 100px;'>Anime não encontrado.</p>";
            exit;
        }
        ?>
        
        <!-- Background dinâmico -->
        <div class="background-image-wrapper" 
            style="background-image: linear-gradient(to right, rgba(0,0,0,0.9) 0%, 
          rgba(0,0,0,0) 60%, rgba(0,0,0,0) 90%, rgba(0,0,0,0.9) 100%), 
            linear-gradient(to bottom, transparent 0%, transparent 20%, rgba(0,0,0,1.0) 100%), 
            <?php if ($anime['imagem_fundo']): ?>
                url('admin/indvanimes/<?= htmlspecialchars($anime['imagem_fundo']) ?>')
            <?php else: ?>
                url('admin/uploads/<?= htmlspecialchars($anime['imagem']) ?>')
            <?php endif; ?>
            ;
            background-size: cover; background-position: center;
            background-repeat: no-repeat;"></div>

        <?php include_once('menu.php');?>
        
        <div class="conteudo-detalhes">
            <div class="textos">
                <h1 class="nome"><?= htmlspecialchars($anime['nome']) ?></h1>
            </div>
            
            <div class="linha">
                <?php if ($anime['classificacao']): ?>
                    <img src="admin/indvanimes/<?= htmlspecialchars($anime['classificacao']) ?>" alt="classificação" id="classificacao1"/>
                <?php endif; ?>
                <span class="legenda22">♦ <?= htmlspecialchars($anime['idioma']) ?> ♦ 
                <?php if ($anime['genero']): ?>
                    <?= htmlspecialchars($anime['genero']) ?>
                <?php endif; ?>
                </span>
            </div>
            
            <div class="estrelas-ilustrativas">
                <div class="estrelas">
                    <span>★</span>
                    <span>★</span>
                    <span>★</span>
                    <span>★</span>
                    <span>★</span>
                </div>
                <div class="legenda">
                    <span class="legenda22"> | Classificação média:</span>
                    <span class="negrito">
                        <?php if ($anime['classificacao_media']): ?>
                            <?= htmlspecialchars($anime['classificacao_media']) ?>
                        <?php else: ?>
                            N/A
                        <?php endif; ?>
                    </span>
                </div>
            </div>             
            
            <div class="botoes">
                <button class="comecar">COMEÇAR A ASSISTIR T1 EP1</button>
                <img src="imagens/SALVAR.PNG" alt="salvar" id="salvar"/>
                <span class="mais">+</span>
            </div>
            
            <div class="textos23">
                <div class="texto1">
                    <?php if ($anime['descricao']): ?>
                        <p><?= nl2br(htmlspecialchars($anime['descricao'])) ?></p>
                    <?php else: ?>
                        <p>Descrição não disponível.</p>
                    <?php endif; ?>
                </div>
                
                <div class="texto2">
                    <div class="audio">
                        <span class="detalheneg">Áudio:</span>
                        <span class="detalhes">
                            <?php if ($anime['audio']): ?>
                                <?= htmlspecialchars($anime['audio']) ?>
                            <?php else: ?>
                                Não informado
                            <?php endif; ?>
                        </span>
                    </div>
                    
                    <div class="legendas">
                        <span class="detalheneg">Legendas:</span>
                        <span class="detalhes">
                            <?php if ($anime['legendas']): ?>
                                <?= htmlspecialchars($anime['legendas']) ?>
                            <?php else: ?>
                                Não informado
                            <?php endif; ?>
                        </span>
                    </div>
                    
                    <?php if ($anime['premios']): ?>
                    <div class="premios">
                        <span class="detalheneg">Prêmios:</span>
                        <span class="detalhes"><?= htmlspecialchars($anime['premios']) ?></span>
                    </div>
                    <?php endif; ?>
                    
                    <div class="classificacao">
                        <span class="detalheneg">Classificação de Conteúdo:</span>
                        <?php if ($anime['classificacao']): ?>
                            <img src="admin/indvanimes/<?= htmlspecialchars($anime['classificacao']) ?>" alt="classificação" id="classificacaoConteudo"/>
                        <?php endif; ?>
                        <span class="detalhes">
                            <?php if ($anime['classificacao_conteudo']): ?>
                                <?= htmlspecialchars($anime['classificacao_conteudo']) ?>
                            <?php else: ?>
                                Não classificado
                            <?php endif; ?>
                        </span>
                    </div>
                    
                    <div class="genero">
                        <span class="detalheneg">Gêneros:</span>
                        <span class="detalhes">
                            <?php if ($anime['genero']): ?>
                                <?= htmlspecialchars($anime['genero']) ?>
                            <?php else: ?>
                                Não informado
                            <?php endif; ?>
                        </span>
                    </div>
                </div>
            </div>
        </div>
    </div>
</body>
</html>