<?php
/**
 * Classe pour gérer les conversions de studios
 */
class StudioConverter {
    private $conversions;
    private $jsonFile;
    
    public function __construct($jsonFile = '../studio-conversions.json') {
        $this->jsonFile = $jsonFile;
        $this->loadConversions();
    }
    
    /**
     * Charger les conversions depuis le fichier JSON
     */
    private function loadConversions() {
        if (file_exists($this->jsonFile)) {
            $json = file_get_contents($this->jsonFile);
            $data = json_decode($json, true);
            $this->conversions = $data['conversions'] ?? [];
        } else {
            $this->conversions = [];
        }
    }
    
    /**
     * Sauvegarder les conversions dans le fichier JSON
     */
    public function saveConversions() {
        $data = ['conversions' => $this->conversions];
        return file_put_contents($this->jsonFile, json_encode($data, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE));
    }
    
    /**
     * Convertir un nom de studio selon les règles définies
     */
    public function convertStudio($studioName) {
        $normalizedInput = strtolower(trim($studioName));
        
        foreach ($this->conversions as $key => $conversion) {
            foreach ($conversion['patterns'] as $pattern) {
                if (strtolower($pattern) === $normalizedInput) {
                    return $conversion['target'];
                }
            }
        }
        
        return $studioName; // Retourner le nom original si aucune conversion trouvée
    }
    
    /**
     * Ajouter une nouvelle règle de conversion
     */
    public function addConversion($key, $patterns, $target) {
        $this->conversions[$key] = [
            'patterns' => is_array($patterns) ? $patterns : [$patterns],
            'target' => $target
        ];
        return $this->saveConversions();
    }
    
    /**
     * Supprimer une règle de conversion
     */
    public function removeConversion($key) {
        if (isset($this->conversions[$key])) {
            unset($this->conversions[$key]);
            return $this->saveConversions();
        }
        return false;
    }
    
    /**
     * Obtenir toutes les conversions
     */
    public function getConversions() {
        return $this->conversions;
    }
    
    /**
     * Mettre à jour une conversion existante
     */
    public function updateConversion($key, $patterns, $target) {
        if (isset($this->conversions[$key])) {
            $this->conversions[$key] = [
                'patterns' => is_array($patterns) ? $patterns : [$patterns],
                'target' => $target
            ];
            return $this->saveConversions();
        }
        return false;
    }
}

// Si le script est appelé directement (pour les requêtes AJAX)
if ($_SERVER['REQUEST_METHOD'] === 'POST' || $_SERVER['REQUEST_METHOD'] === 'GET') {
    header('Content-Type: application/json');
    
    $converter = new StudioConverter();
    $action = $_POST['action'] ?? $_GET['action'] ?? '';
    
    switch ($action) {
        case 'get_conversions':
        case 'list':
            echo json_encode(['success' => true, 'conversions' => $converter->getConversions()]);
            break;
            
        case 'add_conversion':
            $key = $_POST['key'] ?? '';
            $patterns = $_POST['patterns'] ?? [];
            $target = $_POST['target'] ?? '';
            
            if ($key && $patterns && $target) {
                $result = $converter->addConversion($key, $patterns, $target);
                echo json_encode(['success' => $result]);
            } else {
                echo json_encode(['success' => false, 'error' => 'Paramètres manquants']);
            }
            break;
            
        case 'update_conversion':
            $key = $_POST['key'] ?? '';
            $patterns = $_POST['patterns'] ?? [];
            $target = $_POST['target'] ?? '';
            
            if ($key && $patterns && $target) {
                $result = $converter->updateConversion($key, $patterns, $target);
                echo json_encode(['success' => $result]);
            } else {
                echo json_encode(['success' => false, 'error' => 'Paramètres manquants']);
            }
            break;
            
        case 'remove_conversion':
            $key = $_POST['key'] ?? '';
            
            if ($key) {
                $result = $converter->removeConversion($key);
                echo json_encode(['success' => $result]);
            } else {
                echo json_encode(['success' => false, 'error' => 'Clé manquante']);
            }
            break;
            
        case 'convert_studio':
            $studioName = $_POST['studio_name'] ?? '';
            
            if ($studioName) {
                $converted = $converter->convertStudio($studioName);
                echo json_encode(['success' => true, 'original' => $studioName, 'converted' => $converted]);
            } else {
                echo json_encode(['success' => false, 'error' => 'Nom de studio manquant']);
            }
            break;
            
        default:
            echo json_encode(['success' => false, 'error' => 'Action non reconnue']);
    }
    exit;
}
?>