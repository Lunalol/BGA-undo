<?php

/*
 *
 * @author Lunalol - PERRIN Jean-Luc
 *
 */

namespace Bga\Games\YOURGAMENAMEHERE;

trait undo
{
	function clearUndo(): void
	{
		$this->DbQuery("DELETE FROM replaysavepoint WHERE rsp_player_id = 0");
	}
	function checkUndo(): bool
	{
		return (bool) $this->getUniqueValueFromDB("SELECT EXISTS (SELECT * FROM replaysavepoint WHERE rsp_player_id = 0)");
	}
	function undoSavepoint(): void
	{
		self::clearUndo();
//
		parent::undoSavepoint();
	}
	function undoUpdate(): void
	{
		if ($this->getUniqueValueFromDB("SELECT EXISTS (SELECT * FROM zz_savepoint_player)"))
		{
			$move = 1 + $this->getUniqueValueFromDB("SELECT COALESCE(MAX(rsp_move_id), 10000000) FROM replaysavepoint WHERE rsp_player_id = 0");
//
			$tables = [];
			foreach (self::getObjectListFromDB("SHOW TABLES like 'zz_savepoint_%'", true) as $table) $tables[$table] = $this->getObjectListFromDB("SELECT * FROM $table");
//
			$dump = base64_encode(gzcompress(json_encode($tables, JSON_INVALID_UTF8_SUBSTITUTE)));
			$this->DbQuery("INSERT INTO replaysavepoint (rsp_player_id, rsp_move_id, rsp_gamedatas) VALUES (0, $move, '$dump')");
		}
//
		parent::undoSavepoint();
	}
	function undoRestorePoint($undoAll = false): void
	{
		if ($undoAll)
		{
			$dump = $this->getUniqueValueFromDB("SELECT rsp_gamedatas FROM replaysavepoint WHERE rsp_move_id > 10000000 AND rsp_player_id = 0 ORDER BY rsp_move_id ASC LIMIT 1");
			$this->DbQuery("DELETE FROM replaysavepoint WHERE rsp_move_id > 10000000 AND rsp_player_id = 0");
		}
		else
		{
			$dump = $this->getUniqueValueFromDB("SELECT rsp_gamedatas FROM replaysavepoint WHERE rsp_move_id > 10000000 AND rsp_player_id = 0 ORDER BY rsp_move_id DESC LIMIT 1");
			$this->DbQuery("DELETE FROM replaysavepoint WHERE rsp_move_id > 10000000 AND rsp_player_id = 0 ORDER BY rsp_move_id DESC LIMIT 1");
		}
//
		if ($dump)
		{
			foreach (json_decode(gzuncompress(base64_decode($dump)), JSON_OBJECT_AS_ARRAY) as $table => $data)
			{
				$this->DbQuery("DELETE FROM $table");
//
				if ($data)
				{
					$keys = array_keys(reset($data));
//
					$entries = [];
					foreach ($data as $entry)
					{
						$list = [];
						foreach ($entry as $key => $item)
						{
							if ($table === 'zz_savepoint_stats' && $key === 'stats_player_id' && !$item) $list[] = "NULL";
//
							elseif ($table === 'zz_savepoint_player' && $key === 'player_start_reflexion_time' && !$item) $list[] = "NULL";
							elseif ($table === 'zz_savepoint_player' && $key === 'player_remaining_reflexion_time' && !$item) $list[] = "NULL";
							elseif ($table === 'zz_savepoint_player' && $key === 'player_state' && !$item) $list[] = "0";
//
							elseif ($table === 'zz_savepoint_gamelog' && $key === 'gamelog_player' && !$item) $list[] = "NULL";
							elseif ($table === 'zz_savepoint_gamelog' && $key === 'gamelog_move_id' && !$item) $list[] = "NULL";
//
//							elseif ($table === 'zz_savepoint_YOURTABLE' && $key === 'YOURCOLUMN' && !$item) $list[] = "NULL";
//
							else $list[] = "'" . addslashes($item) . "'";
						}
						$entries[] = "(" . implode(",", $list) . ")";
					}
					$this->DbQuery("REPLACE INTO $table (`" . implode("`,`", $keys) . "`) VALUES " . implode(', ', $entries));
				}
			}
//
			parent::undoRestorePoint();
		}
	}
}
