<?php


class db
{
    private $host = '127.0.0.1';
    private $db = 'local';
    private $pass;
    private $user;
    private $charset = 'utf8mb4';
    private $pdo = '';

    public function __construct($user, $pass)
    {
        $this->user = $user;
        $this->pass = $pass;
    }

    public function connect(){

        $dsn = "mysql:host=$this->host;dbname=$this->db;charset=$this->charset";
        $options = [
            PDO::ATTR_ERRMODE            => PDO::ERRMODE_EXCEPTION,
            PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
            PDO::ATTR_EMULATE_PREPARES   => false,
        ];
        try {
            $this->pdo = new PDO($dsn, $this->user, $this->pass, $options);
        } catch (\PDOException $e) {
            throw new \PDOException($e->getMessage(), (int)$e->getCode());
        }
    }

    public function post($data)
    {
        $objects = json_decode($data);
        $this->insertObjectToDB($objects);
    }

    public function insertObjectToDB($objects)
    {
        foreach ($objects as $object) {
            $org_name = $object->org_name;
            if (isset($object->daughters)) {
                $daughters = $this->getDaughters($object->daughters);

                $this->saveToDb($daughters, $org_name);
                $this->insertObjectToDB($object->daughters);
            } else {
                $this->saveToDb(array(),$org_name);
            }
        }
    }

    public function getDaughters($daughters)
    {
        foreach ($daughters as $daughter) {
            $children[] = $daughter->org_name;
        }
        return $children;
    }

    public function saveToDb($daughters, $org_name)
    {

        $query = $this->pdo->prepare("SELECT org_name FROM `relations_table` WHERE org_name=:org_name");
        $query->execute([$org_name]);
        $result = $query->fetch(PDO::FETCH_ASSOC);

        if (empty($result)) {
            $query = $this->pdo->prepare("INSERT INTO `relations_table` (org_name, parent) VALUES ( :org_name, 0)");
            $query->execute([$org_name]);
        }

        foreach ($daughters as $daughter) {
            $query = $this->pdo->prepare("SELECT org_name FROM `relations_table` WHERE org_name=:org_name AND parent=:parent");
            $query->execute([$daughter, $org_name]);
            $result = $query->fetch(PDO::FETCH_ASSOC);
            if (!empty($result)) {
                continue;
            }

            $query = $this->pdo->prepare("INSERT INTO `relations_table` (org_name, parent) VALUES ( :org_name, :daughters)");
            $query->execute([$daughter, $org_name]);
        }
    }

    public function getData($org_name)
    {
        $data = array();

        $parents = $this->getParents($org_name);
        if ($parents) {
            $data['parents'] = $parents;

        }
        $children = $this->getChildren($org_name);
        if ($children) {
            $data['children'] = $children;
        }

        if ($parents) {
            foreach ($parents as $parent) {
                $sisters[] = $this->getChildren($parent['parent']);
            }
            $data['sisters'] = $sisters;
        }

        return $this->createObject($org_name, $data);
    }

    public function getParents($org_name)
    {
        //$org_name = '%'.$org_name.'%';
        $query = $this->pdo->prepare('SELECT DISTINCT parent FROM relations_table WHERE org_name = :org_name');
        $query->execute([$org_name]);
        return $query->fetchAll(PDO::FETCH_ASSOC);
    }

    public function getChildren($org_name)
    {
        $query = $this->pdo->prepare('SELECT DISTINCT org_name FROM relations_table WHERE parent =:org_name');
        $query->execute([$org_name]);
        return $query->fetchAll(PDO::FETCH_ASSOC);
    }

    public function createObject($org_name, $data)
    {
        $object = array();

        if (isset($data['parents'])) {
            $parents = $data['parents'];
            foreach ($parents as $parent) {
                $objects[] = array('relationship_type' => 'parent', 'org_name' => $parent['parent']);
            }
        }
        if (isset($data['children'])) {
            $children = $data['children'];
            foreach ($children as $child) {
                $objects[] = array('relationship_type' => 'daughters', 'org_name' => $child['org_name']);
            }
        }
        if (isset($data['sisters'])) {
            $sisters = $data['sisters'];
            foreach ($sisters[0] as $sister) {
                $result = array_search($sister['org_name'], array_column($objects, 'org_name'));
                if (!$result && $sister['org_name'] !== $org_name)
                    $objects[] = array('relationship_type' => 'sister', 'org_name' => $sister['org_name']);
            }
        }

        $test = array_column($objects, 'org_name');
        array_multisort($test, SORT_ASC, $objects);

        return ($objects);
    }
}