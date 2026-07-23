<?php

namespace Vanderbilt\REDSTAMP\ExternalModule;

class SDTMInterface
{
	public $db;

	public function __construct(string $db_path) {
		$this->db = new \Sqlite3($db_path);
		$this->initDB();
	}

	public function __destruct() {
		$this->db->close();
	}

	public function initDB() {
		$sql = <<<_SQL
			CREATE TABLE IF NOT EXISTS sdtm_mappings (
				uid TEXT PRIMARY KEY,
				domain TEXT,
				instance INT,
				calc_text TEXT
			)
		_SQL;

		$this->exec($sql);
	}

	public function exec(string $sql) {
		return $this->db->exec($sql);
	}

	public function getMappings(string $domain_filter = null) {
		$sql = "SELECT * FROM sdtm_mappings";
		if (!is_null($domain_filter)) {
			// FIXME: consider creating an enum and restricting to these to avoid SQL injection
			$sql .= " WHERE domain = '$domain_filter'";
		}

		$result = $this->db->query($sql);

		$result_arr = [];

		while ($row = $result->fetchArray(SQLITE3_ASSOC)) {
			$result_arr[$row['uid']] = $row;
		}

		return $result_arr;
	}

	public function storeMapping(SDTMMapping $sdtm) {

		$sql = <<<_SQL
			INSERT INTO sdtm_mappings (uid, domain, instance, calc_text) VALUES ('{$sdtm->uid}', '{$sdtm->domain}', '{$sdtm->instance}', '{$sdtm->calc_text}')
			ON CONFLICT(uid)
			DO UPDATE SET
				calc_text = excluded.calc_text;
		_SQL;

		$result = $this->db->exec($sql);

		return $result;

	}
}
