<!DOCTYPE html>
<html lang="pt-br">
<head>
    <meta charset="utf-8" />
    <meta name="viewport" content="width=device-width, initial-scale=1.0" />
    <link href="Crunchyroll.css" rel="stylesheet" />
    <title>Crunchyroll - Temporada Julho 2025</title>
</head>
<body>
    <main>
        <?php include_once('menu.php');?>
        
        <?php
        // Conexão com o banco de dados
        require "admin/conexao.php";
        
        // Buscar os animes do carrossel
        $sql = "SELECT c.*, i.id as detalhe_id 
                FROM carrossel c 
                LEFT JOIN indv_anime i ON c.id = i.id_carrossel 
                ORDER BY c.id";
        $stmt = $pdo->query($sql);
        $animes = $stmt->fetchAll(PDO::FETCH_ASSOC);
        ?>
        
        <section class="Animes">
            <h1 class="indexh1">Uma amostra da temporada de julho de 2025</h1>
            <p class="indexp">Assista os três primeiros episódios desses simulcasts da julho de 2025 de graça!</p>
            
            <?php if (count($animes) > 0): ?>
            <div class="carousel-wrapper">
                <button class="carousel-btn prev">&#10094;</button>
                <div class="anime-list-container">
                    <ul class="anime-list">
                        <?php foreach ($animes as $anime): ?>
                        <li>
                            <?php
                            // Se tem detalhes, vai para a página única de detalhes
                            if ($anime['detalhe_id']) {
                                $link = "detalhes_anime.php?id=" . $anime['id'];
                            } else {
                                // Se não tem detalhes, mantém o link original ou pode criar uma página padrão
                                $link = "#";
                            }
                            ?>
                            <a href="<?= $link ?>" class="anime-link">
                                <img src="admin/uploads/<?= htmlspecialchars($anime['imagem']) ?>" alt="<?= htmlspecialchars($anime['nome']) ?>" />
                                <p class="legenda"><?= htmlspecialchars($anime['nome']) ?></p>
                            </a>
                            <p class="legenda2"><?= htmlspecialchars($anime['idioma']) ?></p>
                        </li>
                        <?php endforeach; ?>
                    </ul>
                </div>
                <button class="carousel-btn next">&#10095;</button>
            </div>
            <?php else: ?>
            <p style="text-align: center; color: #666; margin: 40px 0;">
                Nenhum anime cadastrado no carrossel ainda.
            </p>
            <?php endif; ?>
        </section>
    </main>
    
    <script>
        const prevBtn = document.querySelector('.carousel-btn.prev');
        const nextBtn = document.querySelector('.carousel-btn.next');
        const listContainer = document.querySelector('.anime-list');
        const items = listContainer ? listContainer.querySelectorAll('li') : [];
        
        let position = 0;
        const itemWidth = 300;
        const visibleItems = 4;
        const maxPosition = items.length > 0 ? (items.length - visibleItems) * itemWidth : 0;
        
        function updatePosition() {
            if (listContainer) {
                listContainer.style.transform = `translateX(-${position}px)`;
            }
        }
        
        if (prevBtn && nextBtn) {
            prevBtn.addEventListener('click', () => {
                position -= itemWidth;
                if (position < 0) {
                    position = maxPosition;
                }
                updatePosition();
            });
            
            nextBtn.addEventListener('click', () => {
                position += itemWidth;
                if (position > maxPosition) {
                    position = 0;
                }
                updatePosition();
            });
        }
    </script>
</body>
</html>