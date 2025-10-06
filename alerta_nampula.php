<?php
/**
 * Simulador USSD Local - Alerta Nampula
 * Funciona SEM Africa's Talking - para desenvolvimento e testes
 */

header('Content-Type: text/plain');

// Simular dados USSD (em vez de receber da Africa's Talking)
$sessionId = uniqid();
$serviceCode = '*123#';
$phoneNumber = '+25884XXXXXXX'; // Número simulado
$text = $_GET['text'] ?? ''; // Recebe via GET para testes fáceis

// Inicializar banco de dados SQLite
function initDatabase() {
    try {
        $db = new SQLite3('alerta_nampula.db');
        
        // Criar tabelas
        $db->exec("
            CREATE TABLE IF NOT EXISTS vitimas (
                id INTEGER PRIMARY KEY AUTOINCREMENT,
                nome TEXT NOT NULL,
                telefone TEXT,
                status TEXT DEFAULT 'Precisa de avaliação',
                observacoes TEXT,
                localizacao TEXT,
                data_criacao DATETIME DEFAULT CURRENT_TIMESTAMP
            )
        ");
        
        $db->exec("
            CREATE TABLE IF NOT EXISTS zonas_seguras (
                id INTEGER PRIMARY KEY AUTOINCREMENT,
                nome TEXT NOT NULL,
                endereco TEXT,
                capacidade INTEGER DEFAULT 0,
                ocupacao INTEGER DEFAULT 0,
                data_criacao DATETIME DEFAULT CURRENT_TIMESTAMP
            )
        ");
        
        $db->exec("
            CREATE TABLE IF NOT EXISTS voluntarios (
                id INTEGER PRIMARY KEY AUTOINCREMENT,
                nome TEXT NOT NULL,
                telefone TEXT,
                habilidades TEXT,
                data_criacao DATETIME DEFAULT CURRENT_TIMESTAMP
            )
        ");
        
        $db->exec("
            CREATE TABLE IF NOT EXISTS pedidos_ajuda (
                id INTEGER PRIMARY KEY AUTOINCREMENT,
                telefone TEXT,
                tipo TEXT,
                descricao TEXT,
                localizacao TEXT,
                status TEXT DEFAULT 'Pendente',
                data_criacao DATETIME DEFAULT CURRENT_TIMESTAMP
            )
        ");
        
        $db->exec("
            CREATE TABLE IF NOT EXISTS farmacias (
                id INTEGER PRIMARY KEY AUTOINCREMENT,
                nome TEXT NOT NULL,
                endereco TEXT,
                aberta INTEGER DEFAULT 1
            )
        ");
        
        // Dados de exemplo
        $result = $db->query("SELECT COUNT(*) as count FROM zonas_seguras");
        $row = $result->fetchArray(SQLITE3_ASSOC);
        
        if ($row['count'] == 0) {
            $db->exec("
                INSERT INTO zonas_seguras (nome, endereco, capacidade, ocupacao) VALUES 
                ('Escola Primária', 'Bairro A', 200, 120),
                ('Centro Comunitário', 'Bairro B', 150, 20),
                ('Igreja Central', 'Av. Principal', 100, 45)
            ");
            
            $db->exec("
                INSERT INTO farmacias (nome, endereco, aberta) VALUES 
                ('Farmácia Central', 'Av. 25 de Setembro', 1),
                ('Farmácia Nampula', 'Bairro A', 0),
                ('Farmácia Esperança', 'Bairro C', 1)
            ");
            
            $db->exec("
                INSERT INTO vitimas (nome, telefone, status, observacoes) VALUES 
                ('Maria Joao', '+258840000001', 'Segura', 'Na escola local'),
                ('Carlos Massingue', '+258840000002', 'Precisa de água', 'Zona norte - rua 4')
            ");
        }
        
        return $db;
    } catch (Exception $e) {
        return null;
    }
}

// Processar texto USSD
function processText($text) {
    if (empty($text)) {
        return [];
    }
    return explode('*', $text);
}

// Menu principal
function showMainMenu() {
    $response = "CON ALERTA NAMPULA - EMERGÊNCIA\n";
    $response .= "1. Localizar Vítimas\n";
    $response .= "2. Zonas Seguras\n";
    $response .= "3. Pedir Ajuda\n";
    $response .= "4. Informações\n";
    $response .= "5. Apoio Voluntário\n";
    $response .= "0. Suporte Médico\n";
    $response .= "\nEscolha uma opção:";
    
    return $response;
}

// Menu de localização de vítimas
function showVictimsMenu($textArray, $phoneNumber, $db) {
    if (count($textArray) == 1) {
        $response = "CON LOCALIZAR VÍTIMAS\n";
        $response .= "1. Buscar por nome\n";
        $response .= "2. Buscar por telefone\n";
        $response .= "3. Vítimas na minha zona\n";
        $response .= "4. Reportar vítima\n";
        $response .= "Escolha uma opção:";
        return $response;
    }
    
    $option = $textArray[1];
    
    switch ($option) {
        case '1': // Buscar por nome
            if (count($textArray) == 2) {
                return "CON Digite o nome para buscar:";
            }
            $nome = $textArray[2];
            $stmt = $db->prepare("SELECT * FROM vitimas WHERE nome LIKE ?");
            $stmt->bindValue(1, "%$nome%", SQLITE3_TEXT);
            $result = $stmt->execute();
            
            $response = "CON RESULTADOS PARA: $nome\n";
            $count = 0;
            while ($row = $result->fetchArray(SQLITE3_ASSOC)) {
                $count++;
                $response .= "\n{$row['nome']} - {$row['status']}";
                if (!empty($row['observacoes'])) {
                    $response .= "\nObs: {$row['observacoes']}";
                }
                $response .= "\n---";
            }
            
            if ($count == 0) {
                $response = "END Nenhuma vítima encontrada para: $nome";
            } else {
                $response .= "\n\nFim dos resultados";
            }
            return $response;
            
        case '2': // Buscar por telefone
            if (count($textArray) == 2) {
                return "CON Digite o telefone para buscar:";
            }
            $telefone = $textArray[2];
            $stmt = $db->prepare("SELECT * FROM vitimas WHERE telefone LIKE ?");
            $stmt->bindValue(1, "%$telefone%", SQLITE3_TEXT);
            $result = $stmt->execute();
            
            $response = "CON RESULTADOS:\n";
            $count = 0;
            while ($row = $result->fetchArray(SQLITE3_ASSOC)) {
                $count++;
                $response .= "\n{$row['nome']} - {$row['telefone']}\nStatus: {$row['status']}\n---";
            }
            
            if ($count == 0) {
                $response = "END Nenhuma vítima encontrada";
            }
            return $response;
            
        case '3': // Vítimas na zona
            return "CON Esta funcionalidade requer localização. Contacte 119 para mais informações.";
            
        case '4': // Reportar vítima
            if (count($textArray) == 2) {
                return "CON Digite o nome da vítima:";
            } elseif (count($textArray) == 3) {
                return "CON Digite observações (estado, localização):";
            }
            
            $nome = $textArray[2];
            $obs = $textArray[3];
            
            $stmt = $db->prepare("INSERT INTO vitimas (nome, telefone, observacoes) VALUES (?, ?, ?)");
            $stmt->bindValue(1, $nome, SQLITE3_TEXT);
            $stmt->bindValue(2, $phoneNumber, SQLITE3_TEXT);
            $stmt->bindValue(3, $obs, SQLITE3_TEXT);
            
            if ($stmt->execute()) {
                return "END Vítima reportada com sucesso. Ligue 119 para mais assistência.";
            } else {
                return "END Erro ao reportar vítima. Tente novamente.";
            }
            
        default:
            return "END Opção inválida";
    }
}

// Menu de zonas seguras
function showZonesMenu($textArray, $db) {
    if (count($textArray) == 1) {
        $response = "CON ZONAS SEGURAS\n";
        $response .= "1. Zonas seguras próximas\n";
        $response .= "2. Ver lotação em tempo real\n";
        $response .= "3. Registrar nova zona segura\n";
        $response .= "Escolha uma opção:";
        return $response;
    }
    
    $option = $textArray[1];
    
    switch ($option) {
        case '1': // Listar zonas
            $result = $db->query("SELECT * FROM zonas_seguras");
            $response = "CON ZONAS SEGURAS:\n";
            while ($row = $result->fetchArray(SQLITE3_ASSOC)) {
                $response .= "\n{$row['nome']}\n{$row['endereco']}\nCapacidade: {$row['ocupacao']}/{$row['capacidade']}\n---";
            }
            return $response;
            
        case '2': // Lotação
            $result = $db->query("SELECT * FROM zonas_seguras");
            $response = "CON LOTAÇÃO ATUAL:\n";
            while ($row = $result->fetchArray(SQLITE3_ASSOC)) {
                $percent = $row['capacidade'] > 0 ? round(($row['ocupacao'] / $row['capacidade']) * 100) : 0;
                $response .= "\n{$row['nome']}: {$row['ocupacao']}/{$row['capacidade']} ($percent%)";
            }
            return $response;
            
        case '3': // Registrar zona
            if (count($textArray) == 2) {
                return "CON Digite o nome da zona segura:";
            } elseif (count($textArray) == 3) {
                return "CON Digite o endereço:";
            } elseif (count($textArray) == 4) {
                return "CON Digite a capacidade (número de pessoas):";
            }
            
            $nome = $textArray[2];
            $endereco = $textArray[3];
            $capacidade = intval($textArray[4]);
            
            $stmt = $db->prepare("INSERT INTO zonas_seguras (nome, endereco, capacidade) VALUES (?, ?, ?)");
            $stmt->bindValue(1, $nome, SQLITE3_TEXT);
            $stmt->bindValue(2, $endereco, SQLITE3_TEXT);
            $stmt->bindValue(3, $capacidade, SQLITE3_INTEGER);
            
            if ($stmt->execute()) {
                return "END Zona segura registrada com sucesso!";
            } else {
                return "END Erro ao registrar zona. Tente novamente.";
            }
            
        default:
            return "END Opção inválida";
    }
}

// Menu de pedidos de ajuda
function showHelpMenu($textArray, $phoneNumber, $db) {
    if (count($textArray) == 1) {
        $response = "CON PEDIR AJUDA\n";
        $response .= "1. 🆘 RESGATE URGENTE\n";
        $response .= "2. 🚰 Solicitar ÁGUA\n";
        $response .= "3. 🍚 Solicitar COMIDA\n";
        $response .= "4. 💊 MEDICAMENTOS\n";
        $response .= "5. Reportar danos\n";
        $response .= "Escolha uma opção:";
        return $response;
    }
    
    $option = $textArray[1];
    $tipo = '';
    $descricao = '';
    
    switch ($option) {
        case '1':
            $tipo = 'resgate';
            $descricao = 'Pedido de resgate urgente';
            break;
        case '2':
            $tipo = 'agua';
            if (count($textArray) == 2) {
                return "CON Quantas pessoas precisam de água?";
            }
            $descricao = "Necessidade de água para {$textArray[2]} pessoas";
            break;
        case '3':
            $tipo = 'comida';
            if (count($textArray) == 2) {
                return "CON Quantas pessoas precisam de comida?";
            }
            $descricao = "Necessidade de comida para {$textArray[2]} pessoas";
            break;
        case '4':
            $tipo = 'medicamentos';
            if (count($textArray) == 2) {
                return "CON Qual medicamento precisa?";
            }
            $descricao = "Necessidade de: {$textArray[2]}";
            break;
        case '5':
            $tipo = 'danos';
            if (count($textArray) == 2) {
                return "CON Descreva os danos:";
            }
            $descricao = "Danos reportados: {$textArray[2]}";
            break;
        default:
            return "END Opção inválida";
    }
    
    // Registrar o pedido
    $stmt = $db->prepare("INSERT INTO pedidos_ajuda (telefone, tipo, descricao) VALUES (?, ?, ?)");
    $stmt->bindValue(1, $phoneNumber, SQLITE3_TEXT);
    $stmt->bindValue(2, $tipo, SQLITE3_TEXT);
    $stmt->bindValue(3, $descricao, SQLITE3_TEXT);
    
    if ($stmt->execute()) {
        switch ($tipo) {
            case 'resgate':
                return "END 🆘 Resgate solicitado! Mantenha a calma. Ajuda a caminho.";
            case 'agua':
                return "END 🚰 Pedido de água registrado. Entraremos em contato.";
            case 'comida':
                return "END 🍚 Pedido de comida registrado. Entraremos em contato.";
            case 'medicamentos':
                return "END 💊 Pedido de medicamentos registrado. Contacte farmácias abertas.";
            case 'danos':
                return "END 📝 Danos reportados. Equipa técnica notificada.";
            default:
                return "END Pedido registrado com sucesso!";
        }
    } else {
        return "END Erro ao registrar pedido. Tente novamente.";
    }
}

// Menu de informações
function showInfoMenu($textArray) {
    if (count($textArray) == 1) {
        $response = "CON INFORMAÇÕES\n";
        $response .= "1. Alertas oficiais ativos\n";
        $response .= "2. Previsão meteorológica\n";
        $response .= "3. Estradas cortadas\n";
        $response .= "4. Números de emergência\n";
        $response .= "Escolha uma opção:";
        return $response;
    }
    
    $option = $textArray[1];
    
    switch ($option) {
        case '1':
            return "END ALERTAS ATIVOS:\n- Chuva forte na região\n- Ventos fortes previstos\n- Evite áreas baixas";
        case '2':
            return "END PREVISÃO:\nHoje: Chuva forte\nMáx: 28°C / Mín: 22°C\nVentos: 25-40 km/h";
        case '3':
            return "END ESTRADAS CORTADAS:\n- EN1: Km 12 (queda ponte)\n- Variante Norte (alagada)\nUse rotas alternativas";
        case '4':
            return "END EMERGÊNCIA:\nPolícia: 117\nBombeiros: 118\nSaúde: 119\nAlerta Nampula: 848";
        default:
            return "END Opção inválida";
    }
}

// Menu de voluntariado
function showVolunteerMenu($textArray, $phoneNumber, $db) {
    if (count($textArray) == 1) {
        $response = "CON APOIO VOLUNTÁRIO\n";
        $response .= "1. Registrar-se como voluntário\n";
        $response .= "2. Reportar danos na infraestrutura\n";
        $response .= "3. Oferecer abrigo temporário\n";
        $response .= "4. Doações de recursos\n";
        $response .= "Escolha uma opção:";
        return $response;
    }
    
    $option = $textArray[1];
    
    switch ($option) {
        case '1': // Registrar voluntário
            if (count($textArray) == 2) {
                return "CON Digite seu nome completo:";
            } elseif (count($textArray) == 3) {
                return "CON Que tipo de apoio pode oferecer?\n(ex: transporte, abrigo, comida):";
            }
            
            $nome = $textArray[2];
            $habilidades = $textArray[3];
            
            $stmt = $db->prepare("INSERT INTO voluntarios (nome, telefone, habilidades) VALUES (?, ?, ?)");
            $stmt->bindValue(1, $nome, SQLITE3_TEXT);
            $stmt->bindValue(2, $phoneNumber, SQLITE3_TEXT);
            $stmt->bindValue(3, $habilidades, SQLITE3_TEXT);
            
            if ($stmt->execute()) {
                return "END Obrigado por se voluntariar! Entraremos em contato.";
            } else {
                return "END Erro no registro. Tente novamente.";
            }
            
        case '2': // Reportar danos
            if (count($textArray) == 2) {
                return "CON Descreva os danos na infraestrutura:";
            }
            
            $stmt = $db->prepare("INSERT INTO pedidos_ajuda (telefone, tipo, descricao) VALUES (?, 'danos_infra', ?)");
            $stmt->bindValue(1, $phoneNumber, SQLITE3_TEXT);
            $stmt->bindValue(2, $textArray[2], SQLITE3_TEXT);
            
            if ($stmt->execute()) {
                return "END Danos reportados. Obrigado!";
            }
            break;
            
        case '3': // Oferecer abrigo
            return "END Para oferecer abrigo, contacte diretamente o centro de comando: 848";
            
        case '4': // Doações
            return "END Para doações, contacte:\n- Cruz Vermelha: 843\n- Centro Comunitário: 848";
            
        default:
            return "END Opção inválida";
    }
    
    return "END Erro no processamento";
}

// Menu de suporte médico
function showMedicalMenu($textArray, $db) {
    if (count($textArray) == 1) {
        $response = "CON SUPORTE MÉDICO\n";
        $response .= "1. Localizar unidades de saúde\n";
        $response .= "2. Pedir ambulância\n";
        $response .= "3. Informações de primeiros socorros\n";
        $response .= "4. Farmácias abertas\n";
        $response .= "Escolha uma opção:";
        return $response;
    }
    
    $option = $textArray[1];
    
    switch ($option) {
        case '1':
            return "END UNIDADES DE SAÚDE:\n- Hospital Central Nampula\n- Centro de Saúde Urbano\n- Posto Médico Bairro A\nLigue 119 para emergências";
        case '2':
            return "END 🚑 AMBULÂNCIA SOLICITADA\nMantenha a calma. Ajuda a caminho. Ligue 119 para confirmar.";
        case '3':
            return "END PRIMEIROS SOCORROS:\n1. Verifique respiração\n2. Controle hemorragias\n3. Mantenha a vítima calma\n4. Ligue 119 imediatamente";
        case '4':
            $result = $db->query("SELECT * FROM farmacias WHERE aberta = 1");
            $response = "END FARMÁCIAS ABERTAS:\n";
            while ($row = $result->fetchArray(SQLITE3_ASSOC)) {
                $response .= "\n- {$row['nome']}\n  {$row['endereco']}";
            }
            $response .= "\n\nLigue 119 para emergências médicas";
            return $response;
        default:
            return "END Opção inválida";
    }
}

// Processamento principal
try {
    $db = initDatabase();
    
    if (!$db) {
        echo "END Erro no sistema. Tente novamente.";
        exit;
    }
    
    $textArray = processText($text);
    
    if (empty($textArray)) {
        echo showMainMenu();
    } else {
        $mainOption = $textArray[0];
        
        switch ($mainOption) {
            case '1':
                echo showVictimsMenu($textArray, $phoneNumber, $db);
                break;
            case '2':
                echo showZonesMenu($textArray, $db);
                break;
            case '3':
                echo showHelpMenu($textArray, $phoneNumber, $db);
                break;
            case '4':
                echo showInfoMenu($textArray);
                break;
            case '5':
                echo showVolunteerMenu($textArray, $phoneNumber, $db);
                break;
            case '0':
                echo showMedicalMenu($textArray, $db);
                break;
            default:
                echo "END Opção inválida. Tente novamente.";
        }
    }
    
    $db->close();
    
} catch (Exception $e) {
    echo "END Erro no sistema: " . $e->getMessage();
}
?>