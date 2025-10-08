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
        <section class="Animes">
            <h1 class="indexh1">Uma amostra da temporada de julho de 2025</h1>
            <p class="indexp">Assista os três primeiros episódios desses simulcasts da julho de 2025 de graça!</p>
            <div class="carousel-wrapper">
                <button class="carousel-btn prev">&#10094;</button>
                <div class="anime-list-container">
                    <ul class="anime-list">
                        <li>
                            <a href="http://localhost/projeto/attackontitan.php" class="anime-link">
                                <img src="imagens/attack_on_titan.jpg" alt="Attack on Titan" />
                                <p class="legenda">Attack on Titan</p>
                            </a>
                            <p class="legenda2">Leg | Dub</p>
                        </li>
                        <li>
                            <a href="http://localhost/projeto/demonslayer.php" class="anime-link">
                                <img src="imagens/demon_slayer.jpg" alt="Demon Slayer" />
                                <p class="legenda">Demon Slayer</p>
                            </a>
                            <p class="legenda2">Leg | Dub</p>
                        </li>
                        <li>
                            <a href="http://localhost/projeto/jujutsukaisen.php" class="anime-link">
                                <img src="imagens/jujutsu-kaisen.webp" alt="jujutsu-kaisen" />
                                <p class="legenda">Jujutsu Kaisen</p>
                            </a>
                            <p class="legenda2">Leg | Dub</p>
                        </li>
                        <li>
                            <a href="http://localhost/projeto/erased.php" class="anime-link">
                                <img src="imagens/erased.jpg" alt="Erased" />
                                <p class="legenda">Erased</p>
                            </a>
                            <p class="legenda2">Leg </p>
                        </li>
                        <li>
                            <a href="http://localhost/projeto/gachiakuta.php" class="anime-link">
                                <img src="imagens/gachiakuta.webp" alt="Gachiakuta"/>
                                <p class="legenda">Gachiakuta</p>
                            </a>
                            <p class="legenda2">Leg | Dub</p>
                        </li>
                        <li>
                            <a href="http://localhost/projeto/frieren.php" class="anime-link">
                                <img src="imagens/Frieren.webp" alt="Frieren"/>
                                <p class="legenda">Frieren</p>
                            </a>
                            <p class="legenda2">Leg | Dub</p>
                        </li>
                        <li>
                            <a href="http://localhost/projeto/dandadan.php" class="anime-link">
                                <img src="imagens/Dan Da Dan.webp" alt="Dan Da Dan"/>
                                <p class="legenda">Dan Da Dan</p>
                            </a>
                            <p class="legenda2">Leg | Dub</p>
                        </li>
                        <li>
                            <a href="http://localhost/projeto/spyxfamily.php" class="anime-link">
                                <img src="imagens/SPY x FAMILY.webp" alt="SPY x FAMILY"/>
                                <p class="legenda">SPY x FAMILY</p>
                            </a>
                            <p class="legenda2">Leg | Dub</p>
                        </li>
                        <li>
                            <a href="http://localhost/projeto/sololeveling.php" class="anime-link">
                                <img src="imagens/Solo leveling.jpg" alt="Solo Leveling"/>
                                <p class="legenda">Solo Leveling</p>
                            </a>
                            <p class="legenda2">Leg | Dub</p>
                        </li>
                        <li>
                            <a href="http://localhost/projeto/tokyoghoul.php" class="anime-link">
                                <img src="imagens/Tokyo Ghoul.jpg" alt="Tokyo Ghoul"/>
                                <p class="legenda">Tokyo Ghoul</p>
                            </a>
                            <p class="legenda2">Leg | Dub</p>
                        </li>
                    </ul>
                </div>
                <button class="carousel-btn next">&#10095;</button>
            </div>
        </section>
    </main>
    
    <script>
        const prevBtn = document.querySelector('.carousel-btn.prev');
        const nextBtn = document.querySelector('.carousel-btn.next');
        const listContainer = document.querySelector('.anime-list');
        const items = listContainer.querySelectorAll('li');
        
        let position = 0;
        const itemWidth = 300; // Largura do item + margem
        const visibleItems = 4;
        const maxPosition = (items.length - visibleItems) * itemWidth;
        
        function updatePosition() {
            listContainer.style.transform = `translateX(-${position}px)`;
        }
        
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
    </script>
</body>
</html>