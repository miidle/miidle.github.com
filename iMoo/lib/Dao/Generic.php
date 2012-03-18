<?php

class Dao_Generic
{

	private   $host	 = "localhost"; 	
	private   $port  = "3306";		
	private   $user	 = "imoo";
	private   $pass	 = "password";	
	private   $db 	 = "moodle"; 	
	protected $prefix = "mdl_";		
	private   $con	 = NULL;		
	private   $stm   = NULL;

	/**
	 * Abre a conexão caso ainda não esteja aberta
	 */
	private function open_conection(){
		if($this->con == NULL){
			$dsn = "mysql:host={$this->host};port={$this->port};dbname={$this->db}";
			$this->con = new PDO($dsn, $this->user, $this->pass);
		}
	}

	public function execute($sql, $values){
		$this->open_conection();	
		$this->stm = $this->con->prepare($sql);
		return $this->stm->execute($values);
	}
	
	public function begin_transaction(){
		$this->open_conection();
		$this->con->beginTransaction();
	}

	public function rollback(){
		$this->con->rollBack();
	}

	public function commit(){
		$this->con->commit();
	}

	public function executeInsert($sql, $values){

		try{

			if( $this->execute($sql, $values) ){
				return array('result' => true, 'id' => $this->con->lastInsertId(), 'message' => '');
			} else {
				$erro = $this->stm->errorInfo();
				return array('result' => false, 'id' => '', 'message' => $erro[2]);
			}

		} catch (PDOException $e) {
			return array('result' => false, 'id' => '', 'message' => $e->getMessage());
		}

	}
	
	public function executeUpdateOrDelete($sql, $values){
		
		try{

			if( $this->execute($sql, $values) ){
				return array('result' => true, 'id' => $values[':id'], 'message' => '');
			} else {
				return array('result' => false, 'id' => '', 'message' => $this->stm->errorInfo());
			}

		} catch (PDOException $e) {
			return array('result' => false, 'id' => '', 'message' => $e->getMessage());
		}

	}

	public function query($sql, $values){
		if( $this->execute($sql, $values) ){
			return $this->stm->fetchAll(PDO::FETCH_CLASS);
		} else {
			return array('result' => false, 'id' => '', 'message' => $this->stm->errorInfo());
		}
	}
}
