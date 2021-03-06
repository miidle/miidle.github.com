<?php

define('DEFAULT_DEGREE', 1);

class Dao_Nota extends Dao_Generic
{
	
	function salvar($id_turma, $id_aluno, $nota, $id_avaliacao, $nome_avaliacao)
	{

		if( (empty($id_avaliacao)) || (! $this->_avaliacaoExiste($id_avaliacao, $id_turma))) {
			$result = $this->_insereNovaAvaliacao($id_turma, $nome_avaliacao);
			$id_avaliacao = $result['id'];
		} else {
			$this->_atualizaAvaliacao($id_turma, $id_avaliacao, $nome_avaliacao);
		}
		
		if($this->tem_nota($id_avaliacao, $id_aluno)) {
			return $this->update($id_avaliacao, $id_aluno, $nota);
		} else {
			return $this->insert($id_avaliacao, $id_aluno, $nota);
		}
	}

	protected function _atualizaAvaliacao($id_turma, $id_avaliacao, $nome_avaliacao)
	{
		$sql = "UPDATE acdevaluation SET description = :description WHERE evaluationid = :evaluationid";
		$data = array(
			':evaluationid' => $id_avaliacao,
			':description' => $nome_avaliacao
		);
		return $this->executeUpdateOrDelete($sql, $data);
	}

	protected function _insereNovaAvaliacao($id_turma, $nome_avaliacao)
	{

		$sql = "INSERT INTO acdevaluation (username, datetime, ipaddress, degreeid, description, dateforecast, weight, professorid, groupid) VALUES (:username, :datetime, :ipaddress,
			:degreeid, :description, :dateforecast, :weight, :professorid, :groupid)";

		
		$sql_prof = "SELECT DISTINCT g.professorresponsible as id_professor FROM acdgroup g where groupid = :id_turma";

		$prof = $this->query($sql_prof, array(':id_turma' => $id_turma));

		$data = array(
			':username' => 'sagu2',
			':datetime' => date('Y-m-d H:i:s'),
			':ipaddress' => $_SERVER['REMOTE_ADDR'],
			':degreeid' => DEFAULT_DEGREE,
			':description' => $nome_avaliacao,
			':dateforecast' => date('Y') . '-12-31',
			':weight' => 1,
			':professorid' => $prof[0]->id_professor,
			':groupid' => $id_turma
		);

		return $this->executeInsert($sql, $data, 'seq_evaluationid');
	}

	protected function _avaliacaoExiste($id_avaliacao, $id_turma)
	{
		$sql = "SELECT COUNT(1) as quantidade FROM acdevaluation a INNER JOIN acdgroup g ON g.groupid = a.groupid WHERE g.groupid = :id_turma;";
		$dados = array(':id_turma' => $id_turma);
		$avaliacoes = $this->query($sql, $dados);
		foreach ($avaliacoes as $registro) {
			$quantidade = $registro->quantidade;
		}
		return ($quantidade > 0);
	}

	private function insert($id_avaliacao, $id_aluno, $nota)
	{	
		$sql_insert = "INSERT INTO acdevaluationenroll(evaluationid, enrollid, note) VALUES (:id_avaliacao, (select enrollid from acdenroll e inner join acdcontract c on e.contractid = c.contractid and c.personid = :id_aluno), :nota);";

		$dados = array(':id_avaliacao' => $id_avaliacao, ':id_aluno' => $id_aluno, ':nota' => $nota);

		return $this->executeInsert($sql_insert, $dados, 'seq_evaluationenrollid');
	}

	private function update($id_avaliacao, $id_aluno, $nota){
		
		$id = $this->get_id_nota($id_avaliacao, $id_aluno);

		$sql_update = "UPDATE acdevaluationenroll SET note = :nota where evaluationenrollid = :id;";

		$dados = array(':id' => $id, ':nota' => $nota);
		
		return $this->executeUpdateOrDelete($sql_update, $dados);
	}

	private function tem_nota($id_avaliacao, $id_aluno){
		
		$sql_nota = "SELECT * FROM acdevaluationenroll nota where evaluationid = :id_avaliacao and enrollid =(select enrollid from acdenroll e inner join acdcontract c on e.contractid = c.contractid and c.personid = :id_aluno);";

		$dados = array(':id_avaliacao' => $id_avaliacao, ':id_aluno' => $id_aluno );
		$result = $this->query($sql_nota, $dados);

		return ! empty($result);

	}

	private function get_id_nota($id_avaliacao, $id_aluno){
		$sql_nota = "SELECT nota.evaluationenrollid as id FROM acdevaluationenroll nota where evaluationid = :id_avaliacao and enrollid =(select enrollid from acdenroll e inner join acdcontract c on e.contractid = c.contractid and c.personid = :id_aluno);";

		$dados = array(':id_avaliacao' => $id_avaliacao, ':id_aluno' => $id_aluno );
		$result = $this->query($sql_nota, $dados);
		return $result[0]->id;
	}

	function salvar_old($id_matricula, $nota){

		$sql = "UPDATE acdenroll SET finalnote = :nota WHERE enrollid = :id";
		$dados = array(':nota' => $nota, ':id' => $id_matricula );

		return $this->executeUpdateOrDelete($sql, $dados);

	}

}
