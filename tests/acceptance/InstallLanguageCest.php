<?php

class InstallLanguageCest
{
	public function installLanguage(AcceptanceTester $I)
	{
		$I->am('Administrator');
		$I->installJoomla();
		$I->doAdministratorLogin();
		$I->click("Extensions");
		$I->click('Neno');
		$I->click("#add-languages-button");
		$I->click("(//button[@type='button'])[77]");
		$I->click("div.modal-footer");
		$I->click("Close");
		$I->click("#add-languages-button");
		$I->click("(//button[@type='button'])[77]");
		$I->click("Close");
		$I->doAdministratorLogout();
	}
}