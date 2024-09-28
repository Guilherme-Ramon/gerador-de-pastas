<?php
if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    // Recebe os dados do formulário
    $nomePastaPai = $_POST['nomePastaPai']; // Nome da pasta pai
    $nomePasta = $_POST['nomePasta']; // Nome base das pastas filhos
    $totalPastas = (int)$_POST['totalPastas']; // Total de pastas a serem criadas
    $arquivosSelecionados = $_POST['arquivos']; // Arquivos selecionados pelo usuário
    $organizacao = $_POST['organizacao']; // Se as pastas devem ser organizadas

    // Define a pasta base para a geração dos arquivos
    $baseDir = 'temp/' . $nomePastaPai; // Inclui a pasta pai
    if (!is_dir($baseDir)) {
        mkdir($baseDir, 0777, true);
    }

    // Cria as pastas e arquivos
    for ($i = 1; $i <= $totalPastas; $i++) {
        $pasta = sprintf('%s%03d', $nomePasta, $i); // Formato: Nome001, Nome002...
        $pastaPath = "$baseDir/$pasta";

        if (!is_dir($pastaPath)) {
            mkdir($pastaPath, 0777, true); // Cria a pasta
        }

        // Cria os arquivos selecionados dentro de cada pasta
        foreach ($arquivosSelecionados as $arquivo) {
            $conteudo = '';
            // Define o conteúdo e nome do arquivo conforme o tipo
            if ($arquivo['tipo'] == 'html') {
                $conteudo = "<!-- Conteúdo do index.html -->";
                $nomeArquivo = !empty($arquivo['nome']) ? $arquivo['nome'] . '.html' : 'index.html';
            } elseif ($arquivo['tipo'] == 'css') {
                $conteudo = "/* Conteúdo do style.css */";
                $nomeArquivo = !empty($arquivo['nome']) ? $arquivo['nome'] . '.css' : 'style.css';
            } elseif ($arquivo['tipo'] == 'js') {
                $conteudo = "// Conteúdo do script.js";
                $nomeArquivo = !empty($arquivo['nome']) ? $arquivo['nome'] . '.js' : 'script.js';
            } elseif ($arquivo['tipo'] == 'php') {
                $conteudo = "<?php // Conteúdo do arquivo.php ?>";
                $nomeArquivo = !empty($arquivo['nome']) ? $arquivo['nome'] . '.php' : 'index.php';
            } elseif ($arquivo['tipo'] == 'txt') {
                $conteudo = "Leia-me: Informações sobre o projeto.";
                $nomeArquivo = !empty($arquivo['nome']) ? $arquivo['nome'] . '.txt' : 'readme.txt';
            } elseif ($arquivo['tipo'] == 'json') {
                $conteudo = "{ \"key\": \"value\" }";
                $nomeArquivo = !empty($arquivo['nome']) ? $arquivo['nome'] . '.json' : 'data.json';
            }

            // Cria subpastas se a opção de organização for selecionada
            if ($organizacao === 'sim') {
                $tipo = strtolower($arquivo['tipo']);
                $subpasta = $pastaPath . '/' . $tipo;
                if (!is_dir($subpasta)) {
                    mkdir($subpasta, 0777, true); // Cria a subpasta para o tipo de arquivo
                }
                file_put_contents("$subpasta/$nomeArquivo", $conteudo); // Cria o arquivo na subpasta
            } else {
                file_put_contents("$pastaPath/$nomeArquivo", $conteudo); // Cria o arquivo na pasta principal
            }
        }
    }

    // Cria o arquivo ZIP
    $zip = new ZipArchive();
    $zipFileName = "$nomePastaPai.zip"; // Nome do arquivo ZIP

    if ($zip->open($zipFileName, ZipArchive::CREATE | ZipArchive::OVERWRITE) === TRUE) {
        // Adiciona os arquivos ao ZIP
        $files = new RecursiveIteratorIterator(new RecursiveDirectoryIterator($baseDir), RecursiveIteratorIterator::LEAVES_ONLY);

        foreach ($files as $name => $file) {
            // Ignora diretórios vazios
            if (!$file->isDir()) {
                // Caminho absoluto do arquivo
                $filePath = $file->getRealPath();
                // Remove a parte 'temp/' do caminho relativo
                $relativePath = substr($filePath, strlen('temp/') + 1);
                $zip->addFile($filePath, $relativePath); // Adiciona o arquivo ao ZIP
            }
        }

        // Fecha o arquivo ZIP
        $zip->close();

        // Força o download do arquivo ZIP
        header('Content-Type: application/zip');
        header('Content-disposition: attachment; filename=' . basename($zipFileName));
        header('Content-Length: ' . filesize($zipFileName));
        readfile($zipFileName);

        // Remove os arquivos temporários
        deleteDir('temp'); // Remove o diretório temporário
        if (file_exists($zipFileName)) {
            unlink($zipFileName); // Remove o arquivo ZIP
        }
        exit; // Encerra a execução do script
    } else {
        echo "Erro ao criar o arquivo ZIP.";
    }
}

// Função para remover diretório recursivamente
function deleteDir($dirPath) {
    if (!is_dir($dirPath)) {
        return; // Retorna se não é um diretório
    }

    $files = scandir($dirPath); // Lê os arquivos do diretório
    foreach ($files as $file) {
        if ($file != "." && $file != "..") { // Ignora '.' e '..'
            $filePath = "$dirPath/$file"; // Caminho do arquivo
            if (is_dir($filePath)) {
                deleteDir($filePath); // Chama a função recursivamente para subdiretórios
            } else {
                unlink($filePath); // Remove o arquivo
            }
        }
    }
    rmdir($dirPath); // Remove o diretório
}
?>