<?php

namespace App\Model;
use lib\Model;

class JourneyManager extends Model {

    public function getJourneyList($offset){
        
		$sql = "SELECT *
                FROM journey as a
                INNER JOIN company as b
                ON b.id_company = a.fk_id_company
                ORDER BY a.created_at DESC LIMIT 15 OFFSET ".($offset*15);				
				
            // var_dump($sql);
		$req = $this->dbh->prepare($sql);
        $req->execute();
        $result = $req->fetchAll(\PDO::FETCH_OBJ);

        $sql2 = "SELECT *
                 FROM space 
                 WHERE fk_id_journey = :id_journey";

        $sql3 = "SELECT * FROM stopover WHERE fk_id_journey = :id_journey";

        foreach($result as $res){
            $req = $this->dbh->prepare($sql2);
            $req->bindValue(':id_journey', $res->id_journey);
            $req->execute();
            $spaces = $req->fetchAll(\PDO::FETCH_OBJ);
            $res->spaces = $spaces;

            $req = $this->dbh->prepare($sql3);
            $req->bindValue(':id_journey', $res->id_journey);
            $req->execute();
            $stopovers = $req->fetchAll(\PDO::FETCH_OBJ);
            $res->stopovers = $stopovers;
        }
// var_dump($result);die;
        if ($result) {
            return $result;
        }
        return false;

    }

    public function getJourney($id_journey){

		$sql = "SELECT *
                FROM journey as a
                INNER JOIN company as b
                ON b.id_company = a.fk_id_company
                WHERE id_journey = :id_journey
                ORDER BY a.created_at DESC";				
				
		$req = $this->dbh->prepare($sql);
        $req->bindValue(':id_journey', $id_journey);
        $req->execute();
        $res = $req->fetch(\PDO::FETCH_OBJ);

        $sql2 = "SELECT *
                 FROM space 
                 WHERE fk_id_journey = :id_journey";

            $req = $this->dbh->prepare($sql2);
            $req->bindValue(':id_journey', $id_journey);
            $req->execute();
            $spaces = $req->fetchAll(\PDO::FETCH_OBJ);
            $res->spaces = $spaces;

        $sql3 = "SELECT * FROM stopover WHERE fk_id_journey = :id_journey";

        $req = $this->dbh->prepare($sql3);
        $req->bindValue(':id_journey', $id_journey);
        $req->execute();
        $stopovers = $req->fetchAll(\PDO::FETCH_OBJ);
        $res->stopovers = $stopovers;

        if ($res) {
            return $res;
        }
        return false;

    }

    public function save($data){

        try {

            $this->dbh->beginTransaction();

            $sql = "INSERT INTO `journey`(`departure`, `arrival`, `date_departure`,`date_arrival`, `created_at`, `updated_at`, `fk_id_company`) 
                    VALUES (:departure, :arrival, :date_departure, :date_arrival, :created_at, :updated_at, :fk_id_company)";

            $req = $this->dbh->prepare($sql);
            $req->bindValue(':departure', $data['departure']);
            $req->bindValue(':arrival', $data['arrival']);
            $req->bindValue(':date_departure', strtotime($_POST['date_departure'].''. $_POST['time_departure'].':00'));
            $req->bindValue(':date_arrival', strtotime($_POST['date_arrival'].'01:00'));
            $req->bindValue(':created_at', time());
            $req->bindValue(':updated_at', time());
            $req->bindValue(':fk_id_company', $data['delivery_company']);
            $result = $req->execute();

            if(!$result){
                return false;
            }

            $id_journey = $this->dbh->lastInsertId();

            $sql = "INSERT INTO `stopover`(`nb_stopover`, `city`, `fk_id_journey`) VALUES (:nb_stopover, :city, :fk_id_journey)";

            foreach($data as $key => $val){
                if(\strstr($key, "stop-over-")){
                    $req = $this->dbh->prepare($sql);
                    $req->bindValue(':nb_stopover', intval(str_replace('stop-over-', '', $key)) );
                    $req->bindValue(':city', $val );
                    $req->bindValue(':fk_id_journey', $id_journey);
                    $result = $req->execute();

                    if(!$result){
                        return false;
                    }     
                }
            }
            $sql = "INSERT INTO `space`(`size`, `position`, `pallet_number`, `customer_name`, `goods_nature`, `address`, `date_delivery`, `hour_delivery`, `_top`, `_left`, `created_at`, `updated_at`, `fk_id_journey`, `fk_id_company`) 
                    VALUES (:size, :position, :pallet_number, :customer_name, :goods_nature, :address, :date_delivery, :hour_delivery, :_top, :_left, :created_at, :updated_at, :fk_id_journey, :fk_id_company)";
// var_dump($data['spaces']);die;
            foreach($data['spaces'] as $key => $space){

                if($space['date_delivery'] == ''){
                    $date_delivery = null;
                }else{
                    $date_delivery = strtotime($space['date_delivery'].' 01:00');
                }
                if($space['hour_delivery'] == ''){
                    $hour_delivery = null;
                }else{
                    $hour_delivery = $space['hour_delivery'];
                }
                $req = $this->dbh->prepare($sql);
                $req->bindValue(':size', htmlentities($space['size']));
                $req->bindValue(':position', htmlentities($space['position']));
                $req->bindValue(':pallet_number', htmlentities($space['pallet_number']));
                $req->bindValue(':customer_name', htmlentities($space['customer_name']));
                $req->bindValue(':goods_nature', htmlentities($space['goods_nature']));
                $req->bindValue(':address', htmlentities($space['address']));
                $req->bindValue(':date_delivery', $date_delivery );
                $req->bindValue(':hour_delivery', $hour_delivery );
                $req->bindValue(':_top', htmlentities($space['_top']));
                $req->bindValue(':_left', htmlentities($space['_left']));
                $req->bindValue(':created_at', time());
                $req->bindValue(':updated_at', time());
                $req->bindValue(':fk_id_journey', $id_journey);
                $req->bindValue(':fk_id_company', $_SESSION['id_company']);
                if(!$req->execute()){
                    return false;
                }
                
            }

            $this->dbh->commit();

            return true;
            
        } catch (PDOException $e) {
            $this->dbh->rollback();
            return false;
        }

    }

    public function update($data){

        $this->dbh->beginTransaction();

        try {

            $sql0 = "DELETE FROM `space` WHERE fk_id_journey = :id_journey";

            $req = $this->dbh->prepare($sql0);
            $req->bindValue(':id_journey', $data['id_journey']);
            $result = $req->execute();

            if(!$result){
                return false;
            }

            $sql00 = "DELETE FROM `stopover` WHERE fk_id_journey = :id_journey";

            $req = $this->dbh->prepare($sql00);
            $req->bindValue(':id_journey', $data['id_journey']);
            $result = $req->execute();

            if(!$result){
                return false;
            }

            $sql1 = "UPDATE `journey` 
                     SET `departure`=:departure,
                         `arrival`=:arrival,
                         `date_departure`=:date_departure,
                         `date_arrival`=:date_arrival,
                         `updated_at`=:updated_at,
                         `fk_id_company`=:fk_id_company
                          WHERE id_journey = :id_journey";

            $req = $this->dbh->prepare($sql1);
            $req->bindValue(':departure', $data['departure']);
            $req->bindValue(':arrival', $data['arrival']);
            $req->bindValue(':date_departure', strtotime($_POST['date_departure'].''. $_POST['time_departure'].':00'));
            $req->bindValue(':date_arrival', strtotime($_POST['date_arrival'].'01:00:00'));
            $req->bindValue(':updated_at', time());
            $req->bindValue(':fk_id_company', $data['delivery_company']);
            $req->bindValue(':id_journey', $data['id_journey']);
            $result = $req->execute();

            if(!$result){
                return false;
            }

            $id_journey = $data['id_journey'];

            $sql = "INSERT INTO `space`(`size`, `position`, `pallet_number`, `customer_name`, `goods_nature`, `address`, `date_delivery`, `hour_delivery`, `_top`, `_left`, `created_at`, `updated_at`, `fk_id_journey`, `fk_id_company`) 
                    VALUES (:size, :position, :pallet_number, :customer_name, :goods_nature, :address, :date_delivery, :hour_delivery, :_top, :_left, :created_at, :updated_at, :fk_id_journey, :fk_id_company)";
// var_dump($data['spaces']);die;
            foreach($data['spaces'] as $key => $space){
                
                if($space['date_delivery'] == ''){
                    $date_delivery = null;
                }else{
                    $date_delivery = strtotime($space['date_delivery'].' 01:00');
                    // echo '<br>---------------------<br>';
                    // var_dump($space['pallet_number']);
                    // var_dump($space['date_delivery']);
                    // var_dump($space['date_delivery'].' 01:00');
                    // var_dump($date_delivery );
                    // echo '<br>---------------------<br>';
                }
                if($space['hour_delivery'] == ''){
                    $hour_delivery = null;
                }else{
                    $hour_delivery = $space['hour_delivery'];
                }
                $req = $this->dbh->prepare($sql);
                $req->bindValue(':size', htmlentities($space['size']));
                $req->bindValue(':position', htmlentities($space['position']));
                $req->bindValue(':pallet_number', htmlentities($space['pallet_number']));
                $req->bindValue(':customer_name', htmlentities($space['customer_name']));
                $req->bindValue(':goods_nature', htmlentities($space['goods_nature']));
                $req->bindValue(':address', htmlentities($space['address']));
                $req->bindValue(':date_delivery', $date_delivery );
                $req->bindValue(':hour_delivery', $hour_delivery );
                $req->bindValue(':_top', htmlentities($space['_top']));
                $req->bindValue(':_left', htmlentities($space['_left']));
                $req->bindValue(':created_at', time());
                $req->bindValue(':updated_at', time());
                $req->bindValue(':fk_id_journey', $id_journey);
                $req->bindValue(':fk_id_company', $_SESSION['id_company']);
                    if(!$req->execute()){
                        return false;
                    }
                    
                }

            /////////////
            $sql = "INSERT INTO `stopover`(`nb_stopover`, `city`, `fk_id_journey`) VALUES (:nb_stopover, :city, :fk_id_journey)";
            foreach($data as $key => $val){
                if(\strstr($key, "stop-over-")){
                    $req = $this->dbh->prepare($sql);
                    $req->bindValue(':nb_stopover', intval(str_replace('stop-over-', '', $key)) );
                    $req->bindValue(':city', $val );
                    $req->bindValue(':fk_id_journey', $id_journey);
                    $result = $req->execute();

                    if(!$result){
                        return false;
                    }     
                }
            }

            
        } catch (PDOException $e) {
            $this->dbh->rollback();
            return false;
        }


        $this->dbh->commit();

        return true;

    }

}