<?php
error_reporting(E_ALL);
ini_set('display_errors', 1);

class Category {
    private $id;
    private $name;
    private $parentId;
    private $status;

    public function __construct($id, $name, $parentId = null, $status = 'active') {
        $this->id = $id;
        $this->name = $name;
        $this->parentId = $parentId;
        $this->status = $status;
    }

    public function getId() {
        return $this->id;
    }

    public function getName() {
        return $this->name;
    }

    public function getParentId() {
        return $this->parentId;
    }

    public function getStatus() {
        return $this->status;
    }

    public function setName($name) {
        $this->name = $name;
    }

    public function setStatus($status) {
        $this->status = $status;
    }
}

class CategoryManager {
    private $categories = [];

    public function __construct() {
        if (file_exists('categories.json')) {
            $json = file_get_contents('categories.json');
            $data = json_decode($json, true);
            if (is_array($data)) {
                foreach ($data as $catData) {
                    $category = new Category($catData['id'], $catData['name'], $catData['parentId'], $catData['status']);
                    $this->categories[$catData['id']] = $category;
                }
            }
        }
    }

    public function addCategory($name, $parentId = null, $status = 'active') {
        $id = uniqid();
        $category = new Category($id, $name, $parentId, $status);
        $this->categories[$id] = $category;
        return $category;
    }

    public function editCategory($id, $name, $status = null) {
        if (isset($this->categories[$id])) {
            $category = $this->categories[$id];
            $category->setName($name);
            if ($status !== null) { // Update status only if provided
                $category->setStatus($status);
            }
            return $category;
        }
        return null;
    }

    public function deleteCategory($id) {
        if (isset($this->categories[$id])) {
            unset($this->categories[$id]);
            return true;
        }
        return false;
    }

    public function activateCategory($id) {
        if (isset($this->categories[$id])) {
            $this->categories[$id]->setStatus('active');
            return true;
        }
        return false;
    }

    public function deactivateCategory($id) {
        if (isset($this->categories[$id])) {
            $this->categories[$id]->setStatus('inactive');
            return true;
        }
        return false;
    }

    public function getCategories() {
        $output = [];
        foreach ($this->categories as $category) {
            $parentName = $category->getParentId() 
                ? ($this->categories[$category->getParentId()]->getName() ?? 'N/A') 
                : 'N/A';

            $output[] = [
                'id' => $category->getId(),
                'name' => $category->getName(),
                'parentId' => $category->getParentId(),
                'parentName' => $parentName,
                'status' => $category->getStatus(),
            ];
        }
        return $output;
    }

    public function saveCategories() {
        $data = [];
        foreach ($this->categories as $category) {
            $data[] = [
                'id' => $category->getId(),
                'name' => $category->getName(),
                'parentId' => $category->getParentId(),
                'status' => $category->getStatus(),
            ];
        }
        file_put_contents('categories.json', json_encode($data, JSON_PRETTY_PRINT));
    }
}

header('Access-Control-Allow-Origin: *');
header('Content-Type: application/json');

$request = json_decode(file_get_contents('php://input'), true);
$categoryManager = new CategoryManager();

$response = ['status' => 'success'];

switch ($request['action'] ?? '') {
    case 'add':
        if (!empty($request['name'])) {
            $category = $categoryManager->addCategory($request['name'], $request['parentId'] ?? null);
            $response['newCategory'] = [
                'id' => $category->getId(),
                'name' => $category->getName(),
                'parentId' => $category->getParentId(),
                'parentName' => $categoryManager->getCategories()[$category->getParentId()]['name'] ?? 'N/A',
                'status' => $category->getStatus(),
            ];
        } else {
            $response = ['error' => 'Category name is required'];
        }
        break;

        // Updated switch for 'edit' action
        case 'add':
            if (!empty($request['name'])) {
                $category = $categoryManager->addCategory($request['name'], $request['parentId'] ?? null);
                $response['newCategory'] = [
                    'id' => $category->getId(),
                    'name' => $category->getName(),
                    'parentId' => $category->getParentId(),
                    'status' => $category->getStatus(),
                ];
            } else {
                $response = ['error' => 'Category name is required'];
            }
            break;
        
            case 'edit':
                if (!empty($request['id']) && !empty($request['name'])) {
                    $category = $categoryManager->editCategory(
                        $request['id'],
                        $request['name'],
                        $request['status'] ?? null // Update status if provided
                    );
                    
                    if ($category) {
                        $response['category'] = [
                            'id' => $category->getId(),
                            'name' => $category->getName(),
                            'parentId' => $category->getParentId(),
                            'status' => $category->getStatus(),
                        ];
                        $categoryManager->saveCategories(); // Save the changes
                    } else {
                        $response = ['error' => 'Category not found'];
                    }
                } else {
                    $response = ['error' => 'Category ID and name are required'];
                }
                break;
            
        

    case 'delete':
        if (!empty($request['id']) && $categoryManager->deleteCategory($request['id'])) {
            $response = ['message' => 'Category deleted successfully'];
        } else {
            $response = ['error' => 'Category not found'];
        }
        break;

    case 'activate':
        if (!empty($request['id']) && $categoryManager->activateCategory($request['id'])) {
            $response = ['message' => 'Category activated successfully'];
        } else {
            $response = ['error' => 'Category not found'];
        }
        break;

    case 'deactivate':
        if (!empty($request['id']) && $categoryManager->deactivateCategory($request['id'])) {
            $response = ['message' => 'Category deactivated successfully'];
        } else {
            $response = ['error' => 'Category not found'];
        }
        break;

    default:
        $response = ['error' => 'Invalid action'];
        break;
}

// Save the updated categories after every action
$categoryManager->saveCategories();

// Return the JSON response
echo json_encode($response);
