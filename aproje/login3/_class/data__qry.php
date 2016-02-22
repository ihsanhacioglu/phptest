<?php
abstract class cls__qry{
	protected $dblink=null;

	protected $FieldStr="";
	protected $FromStr="";
	protected $WhereStr="";
	protected $GroupStr="";
	protected $OrderStr="";
	protected $LimitStr="";

	protected $sqltype="";
	protected $info=false;
	protected $state="";
	protected $fro_s=".";

	public  $main=null;
	public	$insert_id=0;
	public  $temp="";
	public  $active=false;
	public  $strSql="";

	public	$id=null;
	public	$ktarih="";
	public	$kuser=-1;
	public	$sirket=-1;
        public  $personel=-1;
	
	public	$arrFrom   =array();
	public  $arrParams =array();
	public  $arrFields =array();
	public	$arrNames  =array();
	public	$arrFroms  =array();
	public	$arrJoins  =array();
	public	$arrUpdates=array();
	public	$arrWhere  =array();
	public	$arrDetail =array();
	public	$arrCals   =array();
	public	$arrOrg	   =array();

	public	$strNames="";
	public	$keyFrom="";
	public	$keyQry=null;
	public	$intFld="";
	public	$reqFld="";
	public	$readFld="";
	public	$reccount=0;
	public	$affected=0;
	public	$senaryo=0;
	public	$setn=null;
	public	$msg="";
	public	$_CCC="";
	public	$_write=0;

	function __construct($nLink,$strSql,$main=null,$setn=null){
		if(empty($nLink))	return false;
		if(empty($strSql))	return false;

		$this->dblink=$nLink;
		$strSql=trim($strSql);
		$this->main=$main;
		$this->setn=$setn;

		$this->findParams($strSql);
		if(preg_match_all("/\b(select|update|insert|delete|from|where|order\s+by|group\s+by|limit)\b/iU",$strSql,$arr_match,PREG_OFFSET_CAPTURE)){
			$arrCla=$arr_match[0];
			$arrCla[count($arrCla)][1]=strlen($strSql);
			for($ii=0; $ii<count($arrCla)-1; $ii++){
				$strExpr=substr($strSql,$arrCla[$ii][1],$arrCla[$ii+1][1]-$arrCla[$ii][1]);
				$strCla=strtolower($arrCla[$ii][0]);
				if($strCla=="select"||$strCla=="update"||$strCla=="insert"||$strCla=="delete"){
					$this->sqltype=$strCla;
					$this->FieldStr=$strExpr;
				}
				elseif ($strCla=="from")  $this->FromStr =$strExpr;
				elseif ($strCla=="where") $this->WhereStr=$strExpr;
				elseif (substr($strCla,0,5)=="order") $this->OrderStr=$strExpr;
				elseif (substr($strCla,0,5)=="group") $this->GroupStr=$strExpr;
				elseif ($strCla=="limit") $this->LimitStr=$strExpr;
			}
		}
		$this->findExps($strSql,$this->FieldStr,$this->strNames);
		$this->strSql=$strSql;
		if($this->sqltype=="select")$this->setFroms();
		return true;
	}
	
	function findParams(&$strSql){
		if(preg_match_all("/(\?prm_(\w+)(:[SNIDTHMWQL])?(\s|\W|$))/i",$strSql,$arr_match,PREG_SET_ORDER))
			foreach($arr_match as $match){
				$ii=stripos($strSql,$match[1]);
				$strSql=substr_replace($strSql,"?$match[4]",$ii,strlen($match[1]));

				$name=$match[2];
				$par_name=strtolower("par_$name");
				if(!isset($this->$par_name)){
					$prm_name="prm_$name";
					$this->$prm_name=null;
					$oPar=(object)array("name"=>$name);
					$oPar->char=empty($match[3])?"X":substr($match[3],1);
					$oPar->value=&$this->$prm_name;
					$this->$par_name=$oPar;
				}
				$this->arrParams[]=&$this->$par_name;
			}
	}

	function findExps(&$strSql,&$FieldStr,&$strNames){
		if($this->sqltype!="select")return;
		if(!preg_match_all('/(<<(.+)>>)/U',$strSql,$arr_match,PREG_SET_ORDER))return;
		$strNames=substr($FieldStr,6);
		$nn=0;
		$this->arrOrg=array();
		foreach($arr_match as $match){
			$ii=stripos($strSql,$match[1]);
			$strSql=substr_replace($strSql,$match[2],$ii,strlen($match[1]));

			$ii=stripos($FieldStr,$match[1]);
			$FieldStr=substr_replace($FieldStr,$match[2],$ii,strlen($match[1]));

			$org="org".++$nn;
			$key="<<$org>>";
			$this->arrOrg[$key]=$match[2];
			$ii=stripos($strNames,$match[1]);
			$strNames=substr_replace($strNames,$org,$ii,strlen($match[1]));
		}
	}

	function get_dbLink(){
		return $this->dblink;
	}
	function derive_qry($sqlStr,$main=null,$setn=null){
		$strClass=get_class($this);
		$qDer=new $strClass($this->get_dbLink(),$sqlStr,$main,$setn);
		return $qDer;
	}
	function derive_tab($strTable=null,$nID=null){
		$oOpt=$this->findOpts($strTable);
		if(!isset($oOpt,$oOpt->dattab))return null;
		
		$clsStr=get_class($this);
		$sqlStr="select * from $oOpt->dattab";
		$tDer=new $clsStr($this->dblink, $sqlStr, null,true);
		$addWhere=empty($nID) ? "1=0" : (isset($oOpt->key)?"$oOpt->key":"id")."=$nID";
		$tDer->open($addWhere);
		if($tDer->reccount==0){
			if(isset($tDer->fld_ktarih))$tDer->rec_ktarih=$tDer->fld_ktarih->emptyval;
			if(isset($tDer->fld_atarih))$tDer->rec_atarih=$tDer->fld_atarih->emptyval;
			if(isset($tDer->fld_dtarih))$tDer->rec_dtarih=$tDer->fld_dtarih->emptyval;

			if(isset($tDer->fld_kuser)) $tDer->rec_kuser =$tDer->fld_kuser->emptyval;
			if(isset($tDer->fld_sirket))$tDer->rec_sirket=$tDer->fld_sirket->emptyval;
			if(isset($tDer->fld_perso)) $tDer->rec_perso =$tDer->fld_perso->emptyval;
			if(isset($tDer->fld_mesul)) $tDer->rec_mesul =$tDer->fld_mesul->emptyval;

			if(isset($tDer->fld_duser)) $tDer->rec_duser =$tDer->fld_duser->emptyval;
			if(isset($tDer->fld_dsaat)) $tDer->rec_dsaat =$tDer->fld_dsaat->emptyval;
			if(isset($tDer->fld_ksaat)) $tDer->rec_ksaat =$tDer->fld_ksaat->emptyval;
		}

		if(isset($oOpt->fld) && preg_match_all("/(-)?(\w+)(\s|$)/U",$oOpt->fld,$arr_fld,PREG_SET_ORDER))
			foreach($arr_fld as $match)if(($fld=$tDer->fieldByName($match[2]))){$fld->read=!empty($match[1]);}
		$tDer->setKeyQry($strTable);
		$tDer->setUpdates($strTable);
		return $tDer;
	}

	function findOpts($strOpt){
		if(!preg_match("/(.+?)(#|$)/",$strOpt,$match))return null;
		$oOpt=null;
		if(preg_match_all("/(.+?)(:|;|,|$)/",$match[1],$arr_opt,PREG_SET_ORDER)){
			$oOpt=(object)array();
			foreach($arr_opt as $opt){
				if(preg_match("/^\s*(\w+)\s*=(.+)$/",$opt[1],$val)){
					$oOpt->$val[1]=trim($val[2]);
				}elseif(count($arr_opt)==1 || $opt[2]==":"){
					if(preg_match("/^\s*((\w+)\\$this->fro_s)?(\w+)\s*$/",$opt[1],$val)){
						$oOpt->from=$val[3];
						$oOpt->table=$val[3];
						$oOpt->dattab=(empty($val[2])?"":$val[1]).$val[3];
					}
				}elseif(preg_match("/^\s*((\w+)\\$this->fro_s(\w+))\s*$/",$opt[1],$val)){
						$oOpt->from=$val[3];
						$oOpt->table=$val[3];
						$oOpt->dattab=$val[1];
				}elseif(preg_match("/^\s*(\w+)\s*$/",$opt[1],$val)){
					$oOpt->$val[1]=1;
				}
			}
			if(isset($oOpt->from)){
				if(!isset($oOpt->table))$oOpt->table=$oOpt->from;
				if(!isset($oOpt->dattab))$oOpt->dattab=$oOpt->table;
			}
		}
		return $oOpt;
	}

	function open($addWhere=null,$next=true){}
	function tally(){return 0;}
	function keyOpen($id=null,$next=true){
		if(!isset($this->keyQry) || empty($id)) return false;
		$this->addParam("{$this->keyQry->from}.{$this->keyQry->orgname}=?prm_pp1:{$this->keyQry->char}");
		$this->prm_pp1=$id;
		if($this->active)$this->close();
		$this->open(null,$next);
	}
	function exec($addWhere=null){}
	function close($dropTemp=false){}

	function getSqlStr($strWhere=""){
		if (empty($strWhere)) return $this->strSql;

		$strWhere=empty($this->WhereStr) ? "where $strWhere" : "$this->WhereStr and $strWhere";
		$strGroup=empty($this->GroupStr) ? "" : " $this->GroupStr";
		$strOrder=empty($this->OrderStr) ? "" : " $this->OrderStr";
		$sqlStr="$this->FieldStr $this->FromStr $strWhere$strGroup$strOrder";
		return $sqlStr;
	}

	function setFroms(){
		$this->arrFroms=array();
		if(preg_match("/\s+join\s+/i",$this->FromStr)){
			preg_match_all("/(from|left|right|inner|join|on)\s+/iU",$this->FromStr,$arr_match,PREG_OFFSET_CAPTURE);
			$arrCla=$arr_match[0];
			$arrCla[count($arrCla)][1]=strlen($this->FromStr);
			for($ii=0; $ii<count($arrCla)-1; $ii++){
				if(!strpos(",from,join",strtolower(trim($arrCla[$ii][0]))))continue;
				$strExpr=substr($this->FromStr,$arrCla[$ii][1],$arrCla[$ii+1][1]-$arrCla[$ii][1]);
				if(preg_match("/\s+(((\w+)\\$this->fro_s)?(\w+))(\s+(as\s+)?(\w+))?\s+/i",substr($strExpr,4),$match)){
					$from=empty($match[7]) ? $match[4] : $match[7];
					$oFro=(object)array("from"=>$from,"datab"=>$match[3],"orgtable"=>$match[4],"dattab"=>$match[1],"fro_s"=>&$this->fro_s);
					$this->arrFroms[$from]=$oFro;
				}
			}
		}
		elseif(preg_match_all("/\s*(((\w+)\\$this->fro_s)?(\w+))(\s+(\w+))?\s*(,|$)/i",substr($this->FromStr,4),$arr_match,PREG_SET_ORDER))
		foreach($arr_match as $match){
			$from=empty($match[6]) ? $match[4] : $match[6];
			$oFro=(object)array("from"=>$from,"datab"=>$match[3],"orgtable"=>$match[4],"dattab"=>$match[1],"fro_s"=>&$this->fro_s);
			$this->arrFroms[$from]=$oFro;
		}
	}

	function setKeyQry($strUpdates=""){
		$oOpt=$this->findOpts($strUpdates);

		// if (preg_match_all("/(\w+)\s*(:(.+))?\s*(;|$)/U",$strUpdates,$arr_match,PREG_SET_ORDER))
		// foreach($arr_match as $match){
			// $oOpt=(object)array();
			// if(!isset($this->arrFroms[$oOpt->from])) continue;
			// if(preg_match_all("/(\w+)\s*(=(.+))?\s*(,|$)/U",$match[3],$arr_opt,PREG_SET_ORDER))
				// foreach($arr_opt as $opt)$oOpt->{"$opt[1]"}=$opt[3];
			// $arrOpt[]=$oOpt;
		// }

		if(!isset($oOpt)){
			$akey=array_keys($this->arrFroms);
			$oOpt=(object)array();
			$oOpt->from=count($akey)?$akey[0]:"";
		}

		$oOpt->key  =isset($oOpt->key)  ? $oOpt->key  : "";
		$oOpt->from =isset($oOpt->from) ? $oOpt->from : "";
		$oOpt->chkey=isset($oOpt->chkey)? $oOpt->chkey: 0;
		
		$oOpt->key   =empty($oOpt->key)?"id":$oOpt->key;
		$this->keyQry=$this->fieldByOrgName($oOpt->from,$oOpt->key);
		$this->keyChg=$oOpt->chkey==1;

		$this->keyRec=null;
		if($this->keyQry)$this->keyRec=&$this->{"rec_{$this->keyQry->name}"};
	}

	function setNames($strNames=null){
		$strNames=(empty($strNames)?(empty($this->strNames)?($this->sqltype=="select"?substr($this->FieldStr,6):""):$this->strNames):$strNames);
		$this->arrNames=array();
		foreach($this->arrFroms as $oFro)$this->setFromFields($oFro);

		if(preg_match_all("/\s*(((\w+)\.)?('?\w+'?|\*))(\s+(as\s+)?(\w+))?\s*(,|$)/iU",$strNames,$arr_match,PREG_SET_ORDER))
		foreach($arr_match as $match){
			$org=$match[4];
			$name=empty($match[7]) ? $org : $match[7];
			$from=$match[3];

			if($name=="*"){
				$arr=array();
				if(empty($from))$arr=&$this->arrFroms;
				elseif(!isset($this->arrFroms[$from]))continue;
				else $arr[$from]=$this->arrFroms[$from];
				foreach($arr as $oFro){
					$this->setFromFields($oFro);
					foreach($oFro->fields as $oFld)
						$this->arrNames[]=(object)array("name"=>$oFld->name,"from"=>$oFro->from,"orgname"=>$oFld->name);
				}
			}elseif(!empty($from)){
				if(array_key_exists("<<$org>>",$this->arrOrg))
				$this->arrNames[]=(object)array("name"=>$name,"from"=>$from,"orgname"=>$this->arrOrg["<<$org>>"]);
				else
				$this->arrNames[]=(object)array("name"=>$name,"from"=>$from,"orgname"=>$org);
			}elseif(preg_match("/^'\w+'$/",$org))
				$this->arrNames[]=(object)array("name"=>$name,"from"=>"","orgname"=>$org);
			elseif(array_key_exists("<<$org>>",$this->arrOrg))
				$this->arrNames[]=(object)array("name"=>$name,"from"=>"","orgname"=>$this->arrOrg["<<$org>>"]);
			else{
				foreach($this->arrFroms as $oFro){
					$this->setFromFields($oFro);
					$fName=strtolower($this->propOrgName($org));
					if(isset($oFro->fields[$fName])){
						$oFld=$oFro->fields[$fName];
						$this->arrNames[]=(object)array("name"=>$oFld->name,"from"=>$oFro->from,"orgname"=>$oFld->name);
					}
				}
			}
		}
	}

	function setFromFields($oFro){}
	function setInfo(){}

	function setJoins(){
		if (!preg_match_all("/\s*(\w+)\.(\w+)\s*=\s*(\w+)\.(\w+)(\s+and|\s*$)/U",$this->WhereStr,$arr_match,PREG_SET_ORDER)) return;
		$this->arrJoins=array();
		$nn=0;
		foreach($arr_match as $match){
			$sol=(object)array("order"=>$nn++,"from"=>$match[1],"orgname"=>$match[2],"fld"=>null,"esit"=>null);
			$sag=(object)array("order"=>$nn++,"from"=>$match[3],"orgname"=>$match[4],"fld"=>null,"esit"=>null);
			if (!isset($this->arrFroms[$sol->from]) || !isset($this->arrFroms[$sag->from])) continue;

			$sol->fld=$this->fieldByOrgName($sol->from,$sol->orgname);
			$sag->fld=$this->fieldByOrgName($sag->from,$sag->orgname);
			$sol->esit=$sag;
			$sag->esit=$sol;
			$this->arrJoins[]=$sol;
			$this->arrJoins[]=$sag;
		}
	}
	function setIntFld($strFld=""){
		$strFld=empty($strFld)?$this->intFld:$strFld;
		if (preg_match_all("/\s*(\w+)\s*(,|$)/U",$strFld,$arr_match,PREG_SET_ORDER))
			foreach($arr_match as $match) if($fld=$this->fieldByName($match[1])) $fld->int=true;
	}
	function setReqFld($strFld=""){
		$strFld=empty($strFld)?$this->reqFld:$strFld;
		if (preg_match_all("/\s*(\w+)\s*(,|$)/U",$strFld,$arr_match,PREG_SET_ORDER))
			foreach($arr_match as $match) if($fld=$this->fieldByName($match[1])) $fld->req=true;
	}
	function setReadFld($strFld=""){
		$strFld=empty($strFld)?$this->readFld:$strFld;
		if (preg_match_all("/\s*(\w+)\s*(,|$)/U",$strFld,$arr_match,PREG_SET_ORDER))
			foreach($arr_match as $match) if($fld=$this->fieldByName($match[1])) $fld->read=true;
	}
	function setUpdates($strUpdates=""){
		if(!$this->info) return false;

		$this->setIntFld();
		$this->setReqFld();
		$this->setReadFld();
		$this->arrUpdates=array();
		$oOpt=$this->findOpts($strUpdates);

		if(isset($oOpt,$oOpt->from,$this->arrFroms[$oOpt->from]))
			$this->setFromUpdate($this->arrFroms[$oOpt->from],$oOpt);

		if(count($this->arrUpdates)>1){
			$this->setJoins();
			foreach($this->arrUpdates as $from=>$oUpd){
				if(isset($oUpd->keyFld->ifd))continue;
				foreach ($this->arrJoins as $oJoin)
				if ($oJoin->from==$from && $oJoin->orgname==$oUpd->keyFld->orgname && is_null($oJoin->fld) && !is_null($oJoin->esit->fld))
					$oUpd->keyFld->ifd=$oJoin->esit->fld;
			}
		}
	}
	function get_SETUP_ID($strTable){return null;}
	function get_AUTO_ID($strTable){return null;}
	function setFromUpdate($oFro=null,$oOpt=null){}
	function fromInsert($oUpd){}
	function fromUpdate($oUpd){}
	
	function addParam($parWhere=null){
		if(preg_match_all("/(\?prm_(\w+)(:[SNIDTHMWQL])?(\s|\W|$))/i",$parWhere,$arr_match,PREG_SET_ORDER))
		foreach($arr_match as $match){
			$ii=stripos($parWhere,$match[1]);
			$parWhere=substr_replace($parWhere,"?$match[4]",$ii,strlen($match[1]));

			$name=$match[2];
			$par_name="par_$name";
			if(!isset($this->$par_name)){
				$prm_name="prm_$name";
				$this->$prm_name=null;
				$oPar=(object)array("name"=>$name);
				$oPar->char=(empty($match[3])?"S":substr($match[3],1));
				$oPar->value=&$this->$prm_name;
				$this->$par_name=$oPar;
			}
			$this->arrParams[]=&$this->$par_name;
		}
		if(!empty($parWhere)){
			$this->strSql=$this->getSqlStr($parWhere);
			$this->WhereStr=empty($this->WhereStr) ? "where $parWhere" : "$this->WhereStr and $parWhere";
		}
	}
	function bindObjVals($objVals){
		foreach($objVals as $name => $value){
			$rec_name="rec_$name";
			if(isset($this->$rec_name))$this->$rec_name=$value;
		}
	}
	function getFldVals($strFields=""){
		$oVals=(object)array();
		if(empty($strFields))
			foreach($this->arrFields as $oFld)$oVals->{"$oFld->name"}=$this->{"rec_$oFld->name"};
		elseif(preg_match_all("/\s*(\w+)\s*(,|$)/U",$strFields,$arr_match,PREG_SET_ORDER))
			foreach($arr_match as $match)if($oFld=$this->fieldByName($match[1]))$oVals->{"$oFld->name"}=$this->{"rec_$oFld->name"};
		return $oVals;
	}
	function blankVals(){
		foreach($this->arrFields as $oFld)$oFld->value=$oFld->emptyval;
	}
	function addCal($name){
		$fcal_name="fcal_$name";
		if(isset($this->$fcal_name)) return false;

		$oCal=(object)array();
		$oCal->name=$name;
		$oCal->order=count($this->arrCals);
		$oCal->emptyval="";
		$oCal->valstr="";
		$this->$fcal_name=$oCal;
		$this->arrCals[] =&$this->$fcal_name;
		$cal_name="cal_$oCal->name";
		$this->$cal_name=$oCal->emptyval;
		$oCal->value=&$this->$cal_name;
		return $oCal;
	}
	function createCals($strCals=null){
		if (!is_string($strCals)) return false;
		$this->arrCals=array();
		if (!preg_match_all("/\s*(\w+)\s*=\s*((\\\$(\w+)\.)?([\w-% ]+))($|\r|\n)/U",$strCals,$arr_match,PREG_SET_ORDER)) return false;
		foreach($arr_match as $match){
			if (!empty($match[3])) if (is_null($value=$this->objVal($match[3],$match[4]))) continue;
			if ($oCal=$this->addCal($match[1])){$oCal->valstr=$match[2]; $oCal->value=$match[2];}
		}
	}
	function setCals($main=null){
		if (is_null($main)) $main=$this;
		foreach($this->arrCals as $oCal)
		if (preg_match_all("/%(\w+)(\W|$)/U",$oCal->valstr,$flds,PREG_SET_ORDER)){
			$value=$oCal->valstr;
			foreach ($flds as $name){
				if ($mFld=$main->fieldByName($name[1]))
				$value=str_replace("%$name[1]",$mFld->value,$value);
			}
			$oCal->value=$value;
		}
	}

	function dataSeek($offset){}
	function next(){}
	function first(){}

	function insert(){
		foreach($this->arrUpdates as $oUpd)if(!$this->fromInsert($oUpd))return false;
		return true;
	}
	function update(){
		foreach($this->arrUpdates as $oUpd)if(!$this->fromUpdate($oUpd))return false;
		return true;
	}
	function delete(){
		foreach($this->arrUpdates as $oUpd)if($this->keyQry==$oUpd->keyFld)if(!$this->fromDelete($oUpd))return false;
		return true;
	}
	function propOrgName($orgname){return $orgname;}
	function fieldByOrgName($from,$orgname){
		$orgname=$this->propOrgName($orgname);
		$retFld=null;
		foreach($this->arrFields as $oFld)if(strcasecmp($oFld->from,$from)==0 && strcasecmp($oFld->orgname,$orgname)==0){$retFld=$oFld;break;}
		return $retFld;
	}
	function fieldByName($name){
		$retFld=null;$fld_name=strtolower("fld_$name");
		if(isset($this->$fld_name))$retFld=$this->$fld_name;
		return $retFld;
	}
	function paramByName($name){
		$retPar=null;$par_name=strtolower("par_$name");
		if(isset($this->$par_name))$retPar=$this->$par_name;
		return $retPar;
	}

	function bindPostName($frm_name){
		if(!isset($_POST[$frm_name]) || substr($frm_name,0,4)!="frm_") return;
		$fld_name="fld_".substr($frm_name,4);
		if(!isset($this->$fld_name))return;

		if(empty($_POST[$frm_name]))
			$this->$fld_name->value=$this->$fld_name->emptyval;
		elseif(strpos(",S",$this->$fld_name->char))
			$this->$fld_name->value=substr($_POST[$frm_name],0,$this->$fld_name->length);
		else $this->$fld_name->value=$_POST[$frm_name];
	}
	function bindPostVals(){
		foreach ($this->arrFields as $oFld){
			$post_name="frm_$oFld->name";
			if (isset($_POST[$post_name]))
				if(empty($_POST[$post_name]))
					$oFld->value=$oFld->emptyval;
				elseif(strpos(",S",$oFld->char))
					$oFld->value=substr($_POST[$post_name],0,$oFld->length);
				else $oFld->value=$_POST[$post_name];

			$post_name="frm2_$oFld->name";
			if (isset($_POST[$post_name]))
				if (empty($_POST[$post_name]))
					$oFld->value2=$oFld->emptyval;
				elseif(strpos(",S",$oFld->char))
					$oFld->value2=substr($_POST[$post_name],0,$oFld->length);
				else $oFld->value2=$_POST[$post_name];
		}
	}

	function bindGetName($frm_name){
		if(!isset($_GET[$frm_name]) || substr($frm_name,0,4)!="frm_") return;
		$fld_name="fld_".substr($frm_name,4);
		if(!isset($this->$fld_name))return;

		$frm_val=$_GET[$frm_name];
		if(empty($frm_val))$this->$fld_name->value=$this->$fld_name->emptyval;
		elseif(strpos(",S",$this->$fld_name->char))
		$this->$fld_name->value=substr($frm_val,0,$this->$fld_name->length);
		else $this->$fld_name->value=$frm_val;
	}
	function bindGetVals(){
		foreach($_GET as $frm_name => $frm_val){
			if(substr($frm_name,0,4)!="frm_")continue;
			$fld_name="fld_".substr($frm_name,4);
			if(!isset($this->$fld_name))continue;

			if(empty($frm_val))$this->$fld_name->value=$this->$fld_name->emptyval;
			elseif(strpos(",S",$this->$fld_name->char))
			$this->$fld_name->value=substr($frm_val,0,$this->$fld_name->length);
			else $this->$fld_name->value=$frm_val;
		}
	}
	function bindGridPostVals($ii=0){
		foreach ($this->arrFields as $oFld){
			$post_name="grd_{$ii}_frm_$oFld->name";
			if(!isset($_POST[$post_name]))continue;

			if(empty($_POST[$post_name]))
				$oFld->value=$oFld->emptyval;
			elseif(strpos(",S",$oFld->char))
				$oFld->value=substr($_POST[$post_name],0,$oFld->length);
			else $oFld->value=$_POST[$post_name];
		}
	}

	function char_type($char){}
	function type_char($fld_type){}
	function empty_val($fld_type){}

	function print_vals(){
		foreach ($this->arrFields as $oFld)
			echo "<br>",$oFld->name,"(",$oFld->char,"): ",is_null($oFld->value) ? "null" : $oFld->value;
	}
	function print_empty(){
		foreach ($this->arrFields as $oFld)
			echo "<br>",$oFld->name,"(",$oFld->char,"): ",is_null($oFld->value) ? "null" : $oFld->emptyval;
	}
	function print_pars(){
		foreach ($this->arrParams as $oPar)
			echo "<br>",$oPar->name,"(",$oPar->char,"): ",is_null($oPar->value) ? "null" : $oPar->value;
	}
	function print_whrs(){
		foreach ($this->arrWhere as $oWhr){
			echo "<br>","$oWhr->fromfld $oWhr->opr <br> &nbsp; &nbsp; ";//,(is_null($oWhr->value) ? "null" : $oWhr->value);
			if(isset($oWhr->cnt))for($ii=1;$ii<=$oWhr->cnt;$ii++)echo (is_null($oWhr->{"val$ii"}) ? "null" : $oWhr->{"val$ii"});
		}
	}
	function print_recs(){
		foreach ($this->arrFields as $oFld)
			echo "<br>",$oFld->name,"(",$oFld->type,"): "," VAL:",$this->{"rec_$oFld->name"}," NUM:";
	}
	function toVal(&$val,$type){
		$ret=true;
		switch($type){
		case "S":
		case "M":
		case "Q":
		case "W": break;
		case "N": $ret=is_numeric($val); break;
		case "I": $ret=is_numeric($val) && intval($val)==$val; break;
		case "D": $ret=true;
					if(preg_match("/^\s*(\d{1,2})[.\/-](\d{1,2})[.\/-](\d{4})\s*$/",$val,$mm))
					$val=date("Y-m-d",strtotime("$mm[3]-$mm[2]-$mm[1]"));
				elseif(preg_match("/^\s*(\d{4})[.\/-](\d{1,2})[.\/-](\d{1,2})\s*$/",$val,$mm))
					$val=date("Y-m-d",strtotime("$mm[1]-$mm[2]-$mm[3]"));
				else$ret=false;
				break;
		case "T": $ret=true;
					if(preg_match("/^\s*(\d{1,2})[.\/-](\d{1,2})[.\/-](\d{4})(\s+(\d{1,2}):(\d{1,2})(:\d{1,2})?)?\s*$/",$val,$mm)){
					$saat =empty($mm[5])?"0" :$mm[5];
					$saat.=empty($mm[6])?":0":$mm[6];
					$saat.=empty($mm[7])?""  :$mm[7];
					$val=date("Y-m-d H:i:s",strtotime("$mm[3]-$mm[2]-$mm[1] $saat"));
				}elseif(preg_match("/^\s*(\d{4})[.\/-](\d{1,2})[.\/-](\d{1,2})(\s+(\d{1,2}):(\d{1,2})(:\d{1,2})?)?\s*$/",$val,$mm)){
					$saat =empty($mm[5])?"0" :$mm[5];
					$saat.=empty($mm[6])?":0":$mm[6];
					$saat.=empty($mm[7])?""  :$mm[7];
					$val=date("Y-m-d H:i:s",strtotime("$mm[1]-$mm[2]-$mm[3] $saat"));
				}else$ret=false;
				break;
		case "H": $ret=preg_match('/^\s*\d{1,2}:d{1,2}(:\d{1,2})?\s*$/',$val); break;
		case "L": $val=$val?1:0;
		}
		return $ret;
	}
	function toTypeChar($val){
		if(is_null($val))return"X";
		if(is_numeric($val))return"N";
		if(preg_match('/^\s*\d{1,2}[.\/-]\d{1,2}[.\/-]\d{4}(\s+\d{1,2}:\d{1,2}(:\d{1,2})?)?\s*$/',$val,$match))return(empty($match[1])?"D":"T");
		if(preg_match('/^\s*\d{4}[.\/-]\d{1,2}[.\/-]\d{1,2}(\s+\d{1,2}:\d{1,2}(:\d{1,2})?)?\s*$/',$val,$match))return(empty($match[1])?"D":"T");
		return"S";
	}
	function to_Indexp($cStr){
		$cStr=strtolower(strtr($cStr,"������������I���gGzZ","kusiockusiociaaskkss"));
		$cStr=strtr($cStr,".,;:~=?/'\+*-+(){}[]_<>&#�$^|%! ","----------------------------------");
		$cStr=strtr($cStr,array("aa"=>"a", "ee"=>"e", "ii"=>"i", "oo"=>"o", "uu"=>"u",
		                        "dd"=>"d", "ss"=>"s", "tt"=>"d",
								"ae"=>"a", "oe"=>"o", "ue"=>"u", "-"=>""));
		return $cStr;
	}
}
