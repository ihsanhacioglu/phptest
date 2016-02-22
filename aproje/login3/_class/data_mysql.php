<?php
function connect_mysql($strConn){
	$oOpt=(object)array();
	$oOpt->host="";
	$oOpt->username="";
	$oOpt->password="";
	$oOpt->database="";
	if(preg_match_all("/(\w+)\s*=(.+)(\r|\n|;|$)/U",$strConn,$arr_opt,PREG_SET_ORDER))
		foreach($arr_opt as $opt)$oOpt->{strtolower(trim("$opt[1]"))}=$opt[2];
	$db_link=mysqli_connect("$oOpt->host", "$oOpt->username", "$oOpt->password") or die("cannot connect");
	mysqli_select_db($db_link, "$oOpt->database") or die("cannot select DB");
	if(isset($oOpt->charset))mysqli_query($db_link,"SET CHARACTER SET '$oOpt->charset' ");
	return $db_link;
}
include_once("$ASIS_T/_class/data__qry.php");
class clsMysql extends cls__qry{
	protected $stmt=null;

	function bind_where(&$addWhere,&$arrBind){
		$strFormat="";
		if(count($this->arrParams)){
			$arrBind[]=&$this->stmt; $arrBind[]=&$strFormat;
			foreach($this->arrParams as $par){
				$prm_name="prm_$par->name";
				$strFormat.=is_numeric($this->$prm_name) ? "d" : "s";
				$arrBind[]=&$this->$prm_name;
			}
		}

		$orl=0;
		$addWhere=empty($addWhere)?"":" and $addWhere";
		if(count($arrBind)==0){$arrBind[]=&$this->stmt; $arrBind[]=&$strFormat;}
		foreach($this->arrWhere as $whr){
			$opr=$whr->opr;

			if($opr==">>")		$oprWhr=">>";
			elseif($opr=="~")	$oprWhr="like";
			elseif($opr=="==")	$oprWhr="=";
			else				$oprWhr=$opr;

			$cnt=0;
			if(preg_match_all("/\s*(!(.+)|(.+))\s*(,|$)/U",$whr->value,$vals,PREG_SET_ORDER))
			foreach ($vals as $vm){
				if(empty($vm[1]))continue;
				$cnt++;
				if($opr=="~")$whr->{"val$cnt"}=str_replace("*","%","$vm[2]$vm[3]%");
				else$whr->{"val$cnt"}="$vm[2]$vm[3]";
				$whr->{"not$cnt"}=empty($vm[3]);
			}
			if($cnt==0)continue;
			if($opr==">>" && $cnt!=2) continue;

			$whr->cnt=$cnt;
			$orWhere="";
			for($ii=1;$ii<=$cnt;$ii++){
				if($whr->{"val$ii"}=="{}")if(isset($whr->fld))$whr->{"val$ii"}=$whr->fld->emptyval;else$whr->{"val$ii"}="";
				$arrBind[]=&$whr->{"val$ii"};
				$strFormat.= isset($whr->fld)?$whr->fld->format : (is_numeric($whr->{"val$ii"})?"d":"s");
				$not=$whr->{"not$ii"}?"not ":"";
				$orWhere.=" or $not$whr->fromfld $oprWhr ?";
			}
			$orWhere=substr($orWhere,4);

			if($whr->orl=="//"){
				if($orl){
					if($opr==">>") $addWhere .= " or ($whr->fromfld between ? and ?)";
					else$addWhere .= " or ($orWhere)";
				}else{
					$orl=1;
					if($opr==">>") $addWhere .= " and (($whr->fromfld between ? and ?)";
					else$addWhere .= " and (($orWhere)";
				}
			}else{
				if($orl){$orl=0;$addWhere.=")";}
				if($opr==">>") $addWhere .= " and ($whr->fromfld between ? and ?)";
				else$addWhere .= " and ($orWhere)";
			}
		}
		if($orl){$orl=0;$addWhere.=")";}
		$addWhere=substr($addWhere,5);
	}

	function open($addWhere=null,$next=true){
		if($this->active) return false;

		$arrBind=array();
		$this->bind_where($addWhere,$arrBind);
		$sqlStr=$this->getSqlStr($addWhere);
		//$this->print_whrs();
		$this->addWhere=$addWhere;
		//echo "<br>WHR: $addWhere";
		//echo "<br>SQL: $sqlStr";

		if (!empty($this->temp)){
			$sqlStr="create table temp.$this->temp as ".$sqlStr;
			$_SESSION["tmptables"][]=$this->temp;
		}
		$this->stmt=mysqli_prepare($this->dblink,$sqlStr);
		if(count($arrBind)>2) call_user_func_array("mysqli_stmt_bind_param",$arrBind);
		if(!mysqli_stmt_execute($this->stmt)){
			echo mysqli_error($this->dblink),"<br/>",$sqlStr;
			return false;
		}

		if (!empty($this->temp)){
			$sqlStr="select * from temp.$this->temp";
			mysqli_stmt_close($this->stmt);
			$this->stmt=mysqli_prepare($this->dblink,$sqlStr);
			if (!mysqli_stmt_execute($this->stmt)){
				echo mysqli_error($this->dblink),"<br/>",$sqlStr;
				return false;
			}
		}
		mysqli_stmt_store_result($this->stmt);
		$this->reccount=mysqli_stmt_num_rows($this->stmt);
		$this->active=true;
		$this->setInfo();
		if($next)$this->next();
		return true;
	}

	function exec($addWhere=null){
		if($this->active) return false;

		$arrBind=array();
		$this->bind_where($addWhere,$arrBind);
		$sqlStr=$this->getSqlStr($addWhere);

		$this->stmt=mysqli_prepare($this->dblink,$sqlStr);
		if (count($arrBind)) call_user_func_array("mysqli_stmt_bind_param",$arrBind);
		if (!mysqli_stmt_execute($this->stmt)){
			echo mysqli_error($this->dblink),"<br/>",$sqlStr;
			return false;
		}
		$this->affected=mysqli_stmt_affected_rows($this->stmt);
		if ($this->sqltype=="insert")$this->insert_id=mysqli_insert_id($this->dblink);
		return true;
	}

	function close($dropTemp=false){
		if($this->active) mysqli_stmt_close($this->stmt);
		if($dropTemp && !empty($this->temp)){
			mysqli_query($this->dblink,"drop table temp.$this->temp");
			$this->temp="";
		}
		$this->active=false;
		$this->reccount=0;
	}
	
	function setFromFields($oFro){
		if(isset($oFro->fields)) return;
		$res=mysqli_query($this->dblink,"select * from $oFro->dattab where 1=0");
		while($oInf=mysqli_fetch_field($res))$oFro->fields[strtolower($this->propOrgName($oInf->name))]=
		(object)array("name"=>$this->propOrgName($oInf->name),"type"=>$oInf->type);
	}

	function setInfo(){
		if (!$this->active) return;
		$arrBind=array();
		if ($this->info){
			$arrBind[]=&$this->stmt;
			foreach($this->arrFields as $oFld)$arrBind[]=&$this->{"rec_$oFld->name"};
			call_user_func_array("mysqli_stmt_bind_result",$arrBind);
			return;
		}

		$this->arrFields=array();
		$res=mysqli_stmt_result_metadata($this->stmt);

		$nn=0;
		$arrBind[]=&$this->stmt;
		while ($oInf=mysqli_fetch_field($res)){
			$oInf->owner	= $this;
			$oInf->order	= $nn++;
			$oInf->from		= $oInf->table;
			$oInf->orgtable	= empty($oInf->orgtable) ? $oInf->table : $oInf->orgtable;
			$oInf->orgname	= empty($oInf->orgname)  ? $oInf->name  : $oInf->orgname;
			$oInf->char		= $this->type_char($oInf->type);
			$oInf->format	= $this->type_format($oInf->type);
			$oInf->emptyval	= $this->empty_val($oInf->type);
			$oInf->like		= strpos(",,15,252,253,254,",",$oInf->type,")>0;
			$oInf->filter	= null;
			$oInf->int		= null;
			$oInf->req		= null;
			$oInf->read		= null;
			$oInf->upd		= null;

			$fld_name="fld_$oInf->name";
			$this->$fld_name=$oInf;
			$this->arrFields[]=&$this->$fld_name;

			$rec_name="rec_$oInf->name";
			$this->$rec_name=$oInf->emptyval;
			$oInf->value=&$this->$rec_name;

			$arrBind[]=&$this->$rec_name;
			$this->$fld_name->tfd=null;
			$this->$fld_name->tab=null;
		}
		call_user_func_array("mysqli_stmt_bind_result",$arrBind);
		$this->info=true;
	}

	function setFromUpdate($oFro=null,$oOpt=null){
		if(empty($oFro))return false;
		$keyname=isset($oOpt,$oOpt->key)&&!empty($oOpt->key) ? $oOpt->key : "id";
		$oUpd=(object)array();
		$oUpd->oFrom	 = $oFro;
		$oUpd->id		 = null;
		$oUpd->stmt		 = null;
		$oUpd->autoInc	 = isset($oOpt,$oOpt->auto) && $oOpt->auto==1;
		$oUpd->setInc	 = isset($oOpt,$oOpt->set)  && $oOpt->set==1;
		$oUpd->denk		 = isset($oOpt,$oOpt->denk)?$oOpt->denk:"";
		$oUpd->keyName	 = $keyname;
		$oUpd->keyFld	 = null;
		$oUpd->arrInsert = null;
		$oUpd->arrInsrt2 = null;
		$oUpd->arrUpdate = null;
		$oUpd->arrDelete = null;
		$oUpd->ts		 = "";
		$oUpd->strInsert = "";
		$oUpd->strInsrt2 = "";
		$oUpd->strUpdate = "";
		$oUpd->strDelete = "";

		$oUpd->frmtInsert= "";
		$oUpd->frmtInsrt2= "";
		$oUpd->frmtUpdate= "";
		$oUpd->frmtDelete= "";

		$oUpd->arrFields = array();

		$nn=-1;
		$res=mysqli_query($this->dblink,"select * from $oFro->dattab where 1=0");
		if (mysqli_errno($this->dblink)) {echo mysqli_error($this->dblink); return false;}
		while($oInf=mysqli_fetch_field($res)){
			$qFld=$this->fieldByOrgName($oFro->from,$oInf->name);
			$nn++;
			if(!is_null($qFld)){
				$qFld->upd=!$qFld->read;
				$fld_name="fld_$nn";
				$rec_name="rec_$nn";
				$qry_name="rec_$qFld->name";
				$oUpd->$fld_name=$qFld;
				$oUpd->$rec_name=&$this->$qry_name;
				$oUpd->arrFields[]=$qFld;

				if($qFld->orgname==$oUpd->keyName)$oUpd->keyFld=$qFld;
			}else{
				$oInf->owner	= $this;
				$oInf->from		= $oFro->from;
				$oInf->orgtable	= empty($oFro->orgtable) ? $oInf->table : $oInf->orgtable;
				$oInf->orgname	= $this->propOrgName(empty($oInf->orgname)  ? $oInf->name  : $oInf->orgname);
				$oInf->char		= $this->type_char($oInf->type);
				$oInf->format	= $this->type_format($oInf->type);
				$oInf->emptyval	= $this->empty_val($oInf->type);
				$oInf->filter	= null;
				$oInf->int		= null;
				$oInf->req		= null;
				$oInf->read		= null;
				$oInf->upd		= false;

				$fld_name="fld_$nn";
				$rec_name="rec_$nn";
				$oUpd->$fld_name=$oInf;
				$oUpd->$rec_name=$oInf->emptyval;
				$oInf->value=&$oUpd->$rec_name;
				$oUpd->arrFields[]=$oInf;

				if($oInf->orgname==$oUpd->keyName)$oUpd->keyFld=$oInf;
			}
	    }
		if(is_null($oUpd->keyFld)) return false;
		$this->arrUpdates[$oFro->from]=$oUpd;

		$strInsFlds="";
		$strInsFld2="";
		$strInsVals="";
		$strInsVal2="";
		$strUpdFlds="";

		$oUpd->arrInsert=array();  $oUpd->arrInsert[]=&$oUpd->stmt;  $oUpd->arrInsert[]=&$oUpd->frmtInsert;
		$oUpd->arrInsrt2=array();  $oUpd->arrInsrt2[]=&$oUpd->stmt;  $oUpd->arrInsrt2[]=&$oUpd->frmtInsrt2;
		$oUpd->arrUpdate=array();  $oUpd->arrUpdate[]=&$oUpd->stmt;  $oUpd->arrUpdate[]=&$oUpd->frmtUpdate;
		$oUpd->arrDelete=array();  $oUpd->arrDelete[]=&$oUpd->stmt;  $oUpd->arrDelete[]=&$oUpd->frmtDelete;
		foreach($oUpd->arrFields as $nn=>$uFld){
			$rec_name   ="rec_$nn";
			$strInsFlds.=",$uFld->orgname";
			$strInsVals.=",?";
			$oUpd->frmtInsert.=$uFld->format;
			$oUpd->arrInsert[]=&$oUpd->$rec_name;
			if($uFld!=$oUpd->keyFld && $uFld->orgname!="ts"){
				$strInsFld2.=",$uFld->orgname";
				$strInsVal2.=",?";
				$oUpd->frmtInsrt2.=$uFld->format;
				$oUpd->arrInsrt2[]=&$oUpd->$rec_name;
				if($uFld->upd){
					$strUpdFlds.=",$uFld->orgname=?";
					$oUpd->frmtUpdate.=$uFld->format;
					$oUpd->arrUpdate[]=&$oUpd->$rec_name;
				}
			}
	    }
		$oUpd->setId = $this->keyChg && $this->keyQry==$oUpd->keyFld;

		$strInsFlds=substr($strInsFlds,1);
		$strInsFld2=substr($strInsFld2,1);
		$strInsVals=substr($strInsVals,1);
		$strInsVal2=substr($strInsVal2,1);

		$oUpd->strInsert="insert into {$oUpd->oFrom->dattab} ($strInsFlds) values ($strInsVals)";
		$oUpd->strInsrt2="insert into {$oUpd->oFrom->dattab} ($strInsFld2) values ($strInsVal2)";
		if(isset($oUpd->fld_ts)){
			$strUpdFlds.=",ts=FROM_UNIXTIME(?)";
			if($oUpd->setId)$strUpdFlds.=",{$oUpd->keyFld->orgname}=?";
			$strUpdFlds=substr($strUpdFlds,1);

			$oUpd->strUpdate="update {$oUpd->oFrom->dattab} set $strUpdFlds where {$oUpd->keyFld->orgname}=? and ts=?";
			$oUpd->frmtUpdate.="i";
			$oUpd->frmtUpdate.=$oUpd->keyFld->format;
			if($oUpd->setId)$oUpd->frmtUpdate.=$oUpd->keyFld->format;
			$oUpd->frmtUpdate.=$oUpd->fld_ts->format;

			$oUpd->arrUpdate[]=&$oUpd->ts;
			$oUpd->arrUpdate[]=&$oUpd->keyFld->value;
			if($oUpd->setId)$oUpd->arrUpdate[]=&$this->id;
			$oUpd->arrUpdate[]=&$oUpd->rec_ts;
			
			$oUpd->strDelete="delete from {$oUpd->oFrom->dattab} where {$oUpd->keyFld->orgname}=? and ts=?";
			$oUpd->frmtDelete.=$oUpd->keyFld->format;
			$oUpd->frmtDelete.=$oUpd->fld_ts->format;
			$oUpd->arrDelete[]=&$oUpd->keyFld->value;
			$oUpd->arrDelete[]=&$oUpd->rec_ts;
		}else{
			if($oUpd->setId)$strUpdFlds.=",{$oUpd->keyFld->orgname}=?";
			$strUpdFlds=substr($strUpdFlds,1);

			$oUpd->strUpdate="update {$oUpd->oFrom->dattab} set $strUpdFlds where {$oUpd->keyFld->orgname}=?";
			$oUpd->frmtUpdate.=$oUpd->keyFld->format;
			if($oUpd->setId)$oUpd->frmtUpdate.=$oUpd->keyFld->format;

			$oUpd->arrUpdate[]=&$oUpd->keyFld->value;
			if($oUpd->setId)$oUpd->arrUpdate[]=&$this->id;

			$oUpd->strDelete="delete from {$oUpd->oFrom->dattab} where {$oUpd->keyFld->orgname}=?";
			$oUpd->frmtDelete.=$oUpd->keyFld->format;
			$oUpd->arrDelete[]=&$oUpd->keyFld->value;
		}
		return true;
	}

	function fromInsert($oUpd){
	global $oUser,$oMesul;
		if(isset($this->fld_ktarih) && empty($this->rec_ktarih))$this->rec_ktarih=date("Y-m-d");
		//if(isset($this->fld_atarih) && empty($this->rec_atarih))$this->rec_atarih=date("Y-m-d");
		if(isset($this->fld_dtarih) && empty($this->rec_dtarih))$this->rec_dtarih=date("Y-m-d");

		if(isset($this->fld_kuser)	&& empty($this->rec_kuser)) $this->rec_kuser =$oUser->id;
		if(isset($this->fld_sirket)	&& empty($this->rec_sirket))$this->rec_sirket=$oUser->sirket;
        if(isset($this->fld_perso)  && empty($this->rec_perso)) $this->rec_perso =$oUser->perso;
        if(isset($this->fld_mesul)  && empty($this->rec_mesul)) $this->rec_mesul =$oMesul->id;

		if(isset($this->fld_duser)  && empty($this->rec_duser))	$this->rec_duser =$oUser->id;
		if(isset($this->fld_dsaat)  && empty($this->rec_dsaat))	$this->rec_dsaat =date("H:i:s");
		if(isset($this->fld_ksaat)  && empty($this->rec_ksaat))	$this->rec_ksaat =date("H:i:s");

		if(isset($this->fld_ts))								$this->rec_ts    =date("Y-m-d H:i:s.u");
		if(isset($this->fld_indexp) && isset($this->fld_exp))	$this->rec_indexp=$this->to_Indexp($this->rec_exp);

		foreach($oUpd->arrFields as $uFld)if($uFld->int && empty($uFld->value))$uFld->value=-1;
		elseif($uFld->char=="D")$this->toVal($uFld->value,$uFld->char);
		if($oUpd->autoInc){
			$oUpd->stmt=mysqli_prepare($this->dblink,$oUpd->strInsrt2);
			call_user_func_array("mysqli_stmt_bind_param",$oUpd->arrInsrt2);
		}else{
			if($oUpd->setInc)$oUpd->keyFld->value=$this->get_SETUP_ID($oUpd->denk?$oUpd->denk:$oUpd->oFrom->orgtable);
			$oUpd->stmt=mysqli_prepare($this->dblink,$oUpd->strInsert);
			call_user_func_array("mysqli_stmt_bind_param",$oUpd->arrInsert);
		}
		if(!mysqli_stmt_execute($oUpd->stmt)){
			$this->msg=mysqli_error($this->dblink);
			return false;
		}
		if($oUpd->autoInc){
			$oUpd->keyFld->value=mysqli_insert_id($this->dblink);
			if(isset($oUpd->keyFld->ifd)) $oUpd->keyFld->ifd->value=$oUpd->keyFld->value;
		}
		mysqli_stmt_close($oUpd->stmt);
		return true;
	}

	function fromUpdate($oUpd){
	global $oUser;
		if(isset($this->fld_dtarih))$this->rec_dtarih=date("Y-m-d");
		if(isset($this->fld_duser))	$this->rec_duser =$oUser->id;
		if(isset($this->fld_dsaat))	$this->rec_dsaat =date("H:i:s");
		if(isset($this->fld_indexp) && isset($this->fld_exp))$this->rec_indexp=$this->to_Indexp($this->rec_exp);

		if(isset($oUpd->fld_ts))$oUpd->ts=time();
		if(isset($oUpd->keyFld->ifd))$oUpd->keyFld->value=$oUpd->keyFld->ifd->value;
		foreach($oUpd->arrFields as $uFld)if($uFld->int && empty($uFld->value))$uFld->value=-1;
		elseif($uFld->char=="D")$this->toVal($uFld->value,$uFld->char);
		$oUpd->stmt=mysqli_prepare($this->dblink,$oUpd->strUpdate);
		call_user_func_array("mysqli_stmt_bind_param",$oUpd->arrUpdate);
		if(!mysqli_stmt_execute($oUpd->stmt)){
			$this->msg=mysqli_error($this->dblink);
			return false;
		}
		mysqli_stmt_close($oUpd->stmt);
		return true;
	}

	function fromDelete($oUpd){
		if(isset($oUpd->keyFld->ifd))$oUpd->keyFld->value=$oUpd->keyFld->ifd->value;
		$oUpd->stmt=mysqli_prepare($this->dblink,$oUpd->strDelete);
		call_user_func_array("mysqli_stmt_bind_param",$oUpd->arrDelete);
		if(!mysqli_stmt_execute($oUpd->stmt)){
			$this->msg=mysqli_error($this->dblink);
			return false;
		}
		mysqli_stmt_close($oUpd->stmt);
		return true;
	}

	function dataSeek($offset){if($offset==-1)$offset=0; mysqli_stmt_data_seek($this->stmt,$offset);}
	function next(){return mysqli_stmt_fetch($this->stmt);}
	function first(){mysqli_stmt_data_seek($this->stmt,0);}

	function type_format($fld_type){
		$str_format="s";
		switch($fld_type){
		case 1:		//TINYINT
		case 2:		//SMALLINT
		case 3:		//INTEGER
		case 8:		//BIGINT
		case 9:		//MEDIUMINT
		case 13:	//YEAR
		case 16:	//BIT
					$str_format="i";
					break;

		case 0:		//DECIMAL
		case 4:		//FLOAT
		case 5:		//DOUBLE
		case 246:	//DECIMAL
					$str_format="d";
					break;

		case 6:		//NULL
		case 7:		//TIMESTAMP
		case 10:	//DATE
		case 11:	//TIME
		case 12:	//DATETIME
		case 14:	//DATE
		case 15:	//VARCHAR
		case 247:	//ENUM
		case 248:	//SET
		case 253:	//VARCHAR
		case 254:	//CHAR
					$str_format="s";
					break;

		case 249:	//TINYBLOB
		case 250:	//MEDIUMBLOB
		case 251:	//LONGBLOB
		case 252:	//BLOB
		case 255:	//GEOMETRY
					$str_format="s";
					break;
		}
		return $str_format;
	}

	function type_char($fld_type){
		$char="S";
		switch($fld_type){
		case 1:		//TINYINT
		case 2:		//SMALLINT
		case 3:		//INTEGER
		case 8:		//BIGINT
		case 9:		//MEDIUMINT
		case 13:	//YEAR
		case 16:	//BIT
					$char="I";
					break;

		case 0:		//DECIMAL
		case 4:		//FLOAT
		case 5:		//DOUBLE
		case 246:	//DECIMAL
					$char="N";
					break;

		case 10:	//DATE
		case 14:	//DATE
					$char="D";
					break;

		case 7:		//TIMESTAMP
		case 12:	//DATETIME
					$char="T";
					break;

		case 11:	//TIME
					$char="H";
					break;

		case 6:		//NULL
		case 247:	//ENUM
		case 248:	//SET
					$char="I";
					break;

		case 15:	//VARCHAR
		case 253:	//VARCHAR
		case 254:	//CHAR
					$char="S";
					break;

		case 249:	//TINYBLOB
		case 250:	//MEDIUMBLOB
		case 251:	//LONGBLOB
		case 252:	//BLOB
					$char="M";
					break;

		case 255:	//GEOMETRY
					$char="W";
					break;
		}
		return $char;
	}

	function empty_val($fld_type){
		$e_value="";
		switch($fld_type){
		case 1:		//TINYINT
		case 2:		//SMALLINT
		case 3:		//INTEGER
		case 8:		//BIGINT
		case 9:		//MEDIUMINT
		case 13:	//YEAR
		case 16:	//BIT
		case 0:		//DECIMAL
		case 4:		//FLOAT
		case 5:		//DOUBLE
		case 246:	//DECIMAL
					$e_value=0;
					break;

		case 6:		//NULL
		case 15:	//VARCHAR
		case 247:	//ENUM
		case 248:	//SET
		case 253:	//VARCHAR
		case 254:	//CHAR
		case 249:	//TINYBLOB
		case 250:	//MEDIUMBLOB
		case 251:	//LONGBLOB
		case 252:	//BLOB
		case 255:	//GEOMETRY
					$e_value="";
					break;

		case 7:		//TIMESTAMP
		case 10:	//DATE
		case 11:	//TIME
		case 12:	//DATETIME
		case 14:	//DATE
					$e_value=null;
					break;
		}
		return $e_value;
	}
}
?>