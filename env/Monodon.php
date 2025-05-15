<?php
class Monodon
{
    protected $obj;
    protected $md;
    protected $con;
    public function __construct($server = "mysql:host=localhost;dbname=usuario", $user = "db", $pass = "pass", $options = [PDO::MYSQL_ATTR_INIT_COMMAND => "SET NAMES 'UTF8'"])

    {
        try {
            $this->con = new PDO($server, $user, $pass, $options);
            $this->con->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
        } catch (PDOException $e) {
            die("Error de conexión: " . $e->getMessage());
        }
    }

    public function getConnection()
    {
        return $this->con;
    }
    function statement_insert_v2($tbl, $arr)
    {
        $tbl_desc = $this->table_detail($tbl);
        $query = "INSERT INTO " . $tbl . "(";
        $dd = "";
        $i = 0;
        foreach ($arr as $key => $value) {
            if ($i == 0) {
                $query .= $key;
                $dd .= ':' . $key;
            } else {
                $query .= ', ' . $key;
                $dd .= ', :' . $key;
            }
            $i++;
        }
        $query .= ') VALUES(' . $dd . ')';
        return $query;
    }
    function statement_insert($tbl, $YN)
    {
        $tbl_desc = $this->table_detail($tbl);
        $query = "INSERT INTO " . $tbl . "(";
        $dd = "";
        if ($YN) {
            for ($i = 0; $i < count($tbl_desc); $i++) {
                if ($i == 0) {
                    $query .= $tbl_desc[$i]['Field'];
                    $dd .= ':' . $tbl_desc[$i]['Field'];
                } else {
                    $query .= ', ' . $tbl_desc[$i]['Field'];
                    $dd .= ', :' . $tbl_desc[$i]['Field'];
                }
            }
        } else {
            for ($i = 1; $i < count($tbl_desc); $i++) {
                if ($i == 1) {
                    $query .= $tbl_desc[$i]['Field'];
                    $dd .= ':' . $tbl_desc[$i]['Field'];
                } else {
                    $query .= ', ' . $tbl_desc[$i]['Field'];
                    $dd .= ', :' . $tbl_desc[$i]['Field'];
                }
            }
        }
        $query .= ') VALUES(' . $dd . ')';
        return $query;
    }
    function statement_update_v2($tbl, $arr)
    {
        //$tbl_desc = $this->table_detail($tbl);
        $query = "UPDATE " . $tbl . " SET ";
        $i = 0;
        foreach ($arr as $key => $value) {
            if ($i == 0) {
                $query .= $key . ' = ' . ':' . $key;
            } else {
                $query .= ', ' . $key . " = " . ':' . $key;
            }
            $i++;
        }
        $query .= ' WHERE id = :id';
        return $query;
    }
    function statement_update($tbl)
    {
        $tbl_desc = $this->table_detail($tbl);
        $query = "UPDATE " . $tbl . " SET ";
        for ($i = 1; $i < count($tbl_desc); $i++) {
            if ($i == 1) {
                $query .= $tbl_desc[$i]['Field'] . ' = ' . ':' . $tbl_desc[$i]['Field'];
            } else {
                $query .= ', ' . $tbl_desc[$i]['Field'] . " = " . ':' . $tbl_desc[$i]['Field'];
            }
        }
        $query .= ' WHERE ' . $tbl_desc[0]['Field'] . ' = ' . ':' . $tbl_desc[0]['Field'];
        return $query;
    }
    function the_date($tbl, $POST, $method, $YN)
    {
        $arr = array();
        $arr2 = $this->table_detail($tbl);
        switch ($method) {
            case 'insert':
                if ($YN) {
                    for ($i = 0; $i < count($arr2); $i++) {
                        $arr[':' . $arr2[$i]['Field']] = $POST[$arr2[$i]['Field']];
                    }
                } else {
                    for ($i = 1; $i < count($arr2); $i++) {
                        $arr[':' . $arr2[$i]['Field']] = $POST[$arr2[$i]['Field']];
                    }
                }
                return $arr;
                break;
            case 'update':
                for ($i = 0; $i < count($arr2); $i++) {
                    $arr[':' . $arr2[$i]['Field']] = $POST[$arr2[$i]['Field']];
                }
                return $arr;
                break;
            case 'delete':
                $arr[':' . $arr2[0]['Field']] = $POST[$arr2[0]['Field']];
                break;
        }
    }

    function insert_data_v2($tbl, $arr)
    {
        foreach ($arr as $key => $value) {
            if (empty($value)) {
                unset($arr->$key);
            }
        }
        $query = $this->statement_insert_v2($tbl, $arr);
        return $this->execute_query($query, get_object_vars($arr));
    }
    function insert_data($tbl, $POST, $YN)
    {
        $query = $this->statement_insert($tbl, $YN);
        $arr = $this->the_date($tbl, $POST, 'insert', $YN);
        return $this->execute_query($query, $arr);
    }
    function update_row($tbl, $arr, $method)
    {
        $qu = "";
        switch ($method) {
            case 'add':
                $qu = "UPDATE " . $tbl . " SET " . $arr['row'] . " = " . $arr['row'] . " + " . $arr['value'] . ' WHERE ' . $arr['id'] . " = " . $arr['vl'];
                break;
            case 'reduce':
                $qu = "UPDATE " . $tbl . " SET " . $arr['row'] . " = " . $arr['row'] . " - " . $arr['value'] . ' WHERE ' . $arr['id'] . " = " . $arr['vl'];
                break;
        }
        try {
            $mbd = $this->obj->openConnection();
            $mbd->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
            $mbd->beginTransaction();
            $query = $mbd->prepare($qu);
            $query->execute();
            $mbd->commit();
            $result = array(
                'Result' => 'OK',
                'Message' => 'OK'
            );
            $this->obj->closeConnection();
            return json_encode($result);
        } catch (Exception $e) {
            $mbd->rollBack();
            $result = array(
                'Result' => 'ERROR',
                'Message' => $e->getMessage()
            );
            $this->obj->closeConnection();
            return json_encode($result);
        }
    }
    function update_value($tbl, $field, $index)
    {
        try {
            $mbd = $this->obj->openConnection();
            $mbd->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
            $mbd->beginTransaction();
            $ui = json_decode($this->get_value('aux', 'value', 'salidas'));
            $n_value = ($ui->value) + 1;
            $query = "UPDATE " . $tbl . " SET " . $field . " = :value WHERE field = :" . $index;
            $stm = $mbd->prepare($query);
            $stm->execute(array(':' . $index => $index, ":value" => $n_value));
            $mbd->commit();
            $result = array(
                'Result' => 'OK',
                'Message' => 'OK'
            );
            $this->obj->closeConnection();
            return json_encode($result);
        } catch (Exception $e) {
            $mbd->rollBack();
            $result = array(
                'Result' => 'ERROR',
                'Message' => $e->getMessage()
            );
            $this->obj->closeConnection();
            return json_encode($result);
        }
    }
    function get_value($tbl, $field, $index)
    {
        $mbd = $this->obj->openConnection();
        $query = $mbd->prepare("SELECT " . $field . " FROM " . $tbl . " WHERE field = ?");
        $query->execute(array($index));
        $this->obj->closeConnection();
        return $this->return_one_row($query);
    }
    function insert_datas($tbl, $POST, $index, $separator, $union)
    {
        $aux = $POST[$index];
        $k = array_keys($union);
        $res = json_decode($this->insert_data($tbl, $POST, true));
        if ($res->Result == 'OK') {
            for ($i = 0; $i < count($aux); $i++) {
                $auz = explode($separator, $aux[$i]['name']);
                $this->insert_data($index, array($auz[0] => $aux[$i]['value'], $auz[1] => $auz[2], $k[0] => $union[$k[0]]), false);
                if ($tbl == 'salidas') {
                    $this->update_row('productos', array('row' => 'stock', 'value' => $aux[$i]['value'], 'id' => 'id', 'vl' => $auz[2]), 'reduce');
                } else {
                    if ($tbl == 'entradas') {
                        $this->update_row('productos', array('row' => 'stock', 'value' => $aux[$i]['value'], 'id' => 'id', 'vl' => $auz[2]), 'add');
                    }
                }
            }
        } else {
            $result = array(
                'Result' => 'ERROR',
                'Message' => 'Algo ha salido terriblemente mal :('
            );
        }
        return $this->update_value('aux', 'value', $tbl);
    }
    function update_data_v2($tbl, $arr)
    {
        foreach ($arr as $key => $value) {
            if (empty($value)) {
                unset($arr->$key);
            }
        }
        $query = $this->statement_update_v2($tbl, $arr);
        return $this->execute_query($query, get_object_vars($arr));
    }
    function update_data($tbl, $POST)
    {
        $query = $this->statement_update($tbl);
        $arr = $this->the_date($tbl, $POST, 'update', false);
        return $this->execute_query($query, $arr);
    }
    function delete_data($tbl, $arr)
    {
        if (is_array($tbl)) {
            for ($i = 0; $i < count($tbl); $i++) {
                $st = $this->where_in_statement($arr[$i]);
                $query = "DELETE FROM " . $tbl[$i] . ' WHERE ' . $st;
                return $this->execute_query($query, $arr[$i]);
            }
        } else {
            $st = $this->where_in_statement($arr);
            $query = "DELETE FROM " . $tbl . ' WHERE ' . $st;
            return $this->execute_query($query, $arr);
        }
    }
    function execute_query($query, $arr)
    {
        try {
            $this->con->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
            $this->con->beginTransaction();
            $query = $this->con->prepare($query);
            $query->execute($arr);
            $lid = $this->con->lastInsertId();
            $this->con->commit();
            $result = array(
                'Result' => 'OK',
                'Message' => 'OK',
                'LID' => $lid
            );
            return json_encode($result);
        } catch (Exception $e) {
            $this->con->rollBack();
            $result = array(
                'Result' => 'ERROR',
                'Message' => $e->getMessage()
            );
            return json_encode($result);
        }
    }
    function select_all($tbl, $dt)
    {
        $query = $this->con->prepare("SELECT * FROM " . $tbl);
        $query->execute();
        if ($dt) {
            return $this->return_json_dt($query);
        } else {
            return $this->return_json($query, true);
        }
    }
    function select_all_where($tbl, $arr, $dt = false)
    {
        $st = $this->where_in_statement($arr);
        $query = $this->con->prepare("SELECT * FROM " . $tbl . ' WHERE ' . $st);
        $query->execute($arr);
        if ($dt) {
            return $this->return_json_dt($query);
        } else {
            return $this->return_json($query, true);
        }
        //return $this->return_json($query, false);
    }
    function return_one_row($query)
    {
        return json_encode($res = $query->fetch(PDO::FETCH_ASSOC));
    }
    function auto_complete($tbl, $arr)
    {
        $mbd = $this->obj->openConnection();
        $st = $this->where_like_in_statement($arr);
        $query = $mbd->prepare("SELECT * FROM " . $tbl . ' WHERE ' . $st);
        $query->execute($arr);
        $this->obj->closeConnection();
        while ($res = $query->fetch(PDO::FETCH_ASSOC)) {
            $values[] = array(
                'id' => $res['id'],
                'value' => $res['producto']
            );
        }
        return json_encode($values);
    }
    function select_one($tbl, $arr)
    {
        $st = $this->where_in_statement($arr);
        $query = $this->con->prepare("SELECT * FROM " . $tbl . ' WHERE ' . $st);
        $query->execute($arr);
        return $this->return_one_row($query);
    }
    function where_like_in_statement($arr)
    {
        $st = "";
        $ke = array_keys($arr);
        for ($i = 0; $i < count($arr); $i++) {
            if ($i == 0) {
                $st = $ke[$i] . " LIKE " . ':' . $ke[$i] . " ";
            } else {
                $st .= " OR " . $ke[$i] . " LIKE " . ':' . $ke[$i] . " ";
            }
        }
        return $st;
    }
    function where_in_statement($arr)
    {
        $st = "";
        $ke = array_keys($arr);
        for ($i = 0; $i < count($arr); $i++) {
            if ($i == 0) {
                $st = $ke[$i] . " = " . ':' . $ke[$i];
            } else {
                $st .= " AND " . $ke[$i] . " = " . ':' . $ke[$i];
            }
        }
        return $st;
    }
    function table_detail($tbl)
    {
        $query = $this->con->prepare("DESC " . $tbl . ";");
        $query->execute();
        $values = array();
        while ($res = $query->fetch(PDO::FETCH_ASSOC)) {
            $values[] = $res;
        }
        return $values;
    }
    function return_json($query, $format = true)
    {
        $values = array();
        while ($res = $query->fetch(PDO::FETCH_ASSOC)) {
            $values[] = $res;
        }
        if ($format) {
            $result = array(
                'Result' => 'OK',
                'Records' => $values
            );
            return json_encode($result);
        } else {
            return json_encode($values);
        }
    }
    function return_json_dt($query)
    {
        $values = array();
        while ($res = $query->fetch(PDO::FETCH_ASSOC)) {
            $values[] = $res;
        }
        return json_encode($values);
    }
    function lista_alertas($tbl)
    {
        $mbd = $this->obj->openConnection();
        $query = $mbd->prepare("SELECT * FROM " . $tbl . " WHERE stock <= 11");
        $query->execute();
        $this->obj->closeConnection();
        return $this->return_json($query);
    }
    function run_query($sql, $format = false)
    {
        $query = $this->con->prepare($sql);
        $query->execute();
        return $this->return_json($query, $format);
    }
    function multiple_querys($tables, $datas)
    {
        try {
            $this->con->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
            $this->con->beginTransaction();
            foreach ($tables as $key => $value) {
                $query = $this->statement_insert($value, false);
                $arr = $this->the_date($value, $datas[$key], 'insert', false);
                $query = $this->con->prepare($query);
                $query->execute($arr);
            }
            $this->con->commit();
            $result = array(
                'Result' => 'OK',
                'Message' => 'OK'
            );
            return json_encode($result);
        } catch (Exception $e) {
            $this->con->rollBack();
            $result = array(
                'Result' => 'ERROR',
                'Message' => $e->getMessage()
            );
            return json_encode($result);
        }
    }
    function select_one_with_childs($tables, $where_father, $where_child)
    {
        try {
            $this->con->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
            $this->con->beginTransaction();
            $st = $this->where_in_statement($where_father);
            $query = $this->con->prepare("SELECT * FROM " . $tables[0] . ' WHERE ' . $st);
            $query->execute($where_father);
            $arr_father = json_decode($this->return_one_row($query), true);
            $arr_child = $this->select_all_where($tables[1], $where_child);
            $arr_father[] = ["childs" => json_decode($arr_child, true)];
            $this->con->commit();
            return json_encode($arr_father);
        } catch (Exception $e) {
            $this->con->rollBack();
            $result = array(
                'Result' => 'ERROR',
                'Message' => $e->getMessage()
            );
            return json_encode($result);
        }
    }
    function update_with_delete($tables, $datas, $table_clear, $where_d_t, $where_u_t)
    {
        $this->delete_data($table_clear, $where_d_t);
        try {
            $this->con->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
            $this->con->beginTransaction();
            $query = $this->statement_update($tables[0]);
            $arr = $this->the_date($tables[0], $datas[0], 'update', false);
            $query = $this->con->prepare($query);

            $query->execute($arr);
            for ($i = 1; $i < count($tables); $i++) {
                $query = $this->statement_insert($tables[$i], false);
                $arr = $this->the_date($tables[$i], $datas[$i], 'insert', false);
                $query = $this->con->prepare($query);
                $query->execute($arr);
            }
            $this->con->commit();
            $result = array(
                'Result' => 'OK'
            );
            return json_encode($result);
        } catch (Exception $e) {
            $this->con->rollBack();
            $result = array(
                'Result' => 'ERROR',
                'Message' => $e->getMessage()
            );
            return json_encode($result);
        }
    }
    function executor($query, $method)
    {
        try {
            $this->con->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
            $this->con->beginTransaction();

            switch ($method) {
                case 'select':
                    $q = $this->con->prepare($query);
                    $q->execute();
                    return $this->return_json($q);
                    break;
                case 'update':
                    $q = $this->con->prepare($query);
                    $q->execute();
                    break;
            }
            $this->con->commit();
            $result = array(
                'Result' => 'OK'
            );
            return json_encode($result);
        } catch (Exception $e) {
            $this->con->rollBack();
            $result = array(
                'Result' => 'ERROR',
                'Message' => $e->getMessage()
            );
            return json_encode($result);
        }
    }
    function get_id_last_inserted()
    {
        return $last_id = $this->con->insert_id;
    }
}
