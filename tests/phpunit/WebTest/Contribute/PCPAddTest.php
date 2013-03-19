<?php
/*
 +--------------------------------------------------------------------+
 | CiviCRM version 4.3                                                |
 +--------------------------------------------------------------------+
 | Copyright CiviCRM LLC (c) 2004-2013                                |
 +--------------------------------------------------------------------+
 | This file is a part of CiviCRM.                                    |
 |                                                                    |
 | CiviCRM is free software; you can copy, modify, and distribute it  |
 | under the terms of the GNU Affero General Public License           |
 | Version 3, 19 November 2007 and the CiviCRM Licensing Exception.   |
 |                                                                    |
 | CiviCRM is distributed in the hope that it will be useful, but     |
 | WITHOUT ANY WARRANTY; without even the implied warranty of         |
 | MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.               |
 | See the GNU Affero General Public License for more details.        |
 |                                                                    |
 | You should have received a copy of the GNU Affero General Public   |
 | License along with this program; if not, contact CiviCRM LLC       |
 | at info[AT]civicrm[DOT]org. If you have questions about the        |
 | GNU Affero General Public License or the licensing of CiviCRM,     |
 | see the CiviCRM license FAQ at http://civicrm.org/licensing        |
 +--------------------------------------------------------------------+
 */

require_once 'CiviTest/CiviSeleniumTestCase.php';
class WebTest_Contribute_PCPAddTest extends CiviSeleniumTestCase {

  protected function setUp() {
    parent::setUp();
  }

  function testPCPAdd() {
    // open browser, login
    $this->webtestLogin();

    // set pcp supporter name and email
    $firstName = 'Ma' . substr(sha1(rand()), 0, 4);
    $lastName = 'An' . substr(sha1(rand()), 0, 7);
    $middleName = 'Mid' . substr(sha1(rand()), 0, 7);
    $email = substr(sha1(rand()), 0, 7) . '@example.org';

    $this->openCiviPage("admin/domain", "action=update&reset=1", '_qf_Domain_cancel-bottom');
    $this->type('name', 'DefaultDomain');
    $this->type('email_name', $firstName);
    $this->type('email_address', $email);

    $this->click('_qf_Domain_next_view-bottom');
    $this->waitForPageToLoad($this->getTimeoutMsec());
    $this->assertTrue($this->isTextPresent("Domain information for 'DefaultDomain' has been saved."),
      "Status message didn't show up after saving!"
    );

    require_once 'ContributionPageAddTest.php';

    // a random 7-char string and an even number to make this pass unique
    $hash = substr(sha1(rand()), 0, 7);
    $rand = $contributionAmount = 2 * rand(2, 50);
    $pageTitle = 'PCP Contribution' . $hash;
    $processorType = 'Dummy';
    $processorName = "Webtest Dummy" . substr(sha1(rand()), 0, 7);
    $amountSection = TRUE;
    $payLater = TRUE;
    $onBehalf = FALSE;
    $pledges = FALSE;
    $recurring = FALSE;
    $memberships = FALSE;
    $memPriceSetId = NULL;
    $friend = FALSE;
    $profilePreId = NULL;
    $profilePostId = NULL;
    $premiums = FALSE;
    $widget = FALSE;
    $pcp = TRUE;
    $isAprovalNeeded = TRUE;

    // create a new online contribution page with pcp enabled
    // create contribution page with randomized title and default params
    $pageId = $this->webtestAddContributionPage($hash,
      $rand,
      $pageTitle,
      array($processorName => $processorType),
      $amountSection,
      $payLater,
      $onBehalf,
      $pledges,
      $recurring,
      $memberships,
      $memPriceSetId,
      $friend,
      $profilePreId,
      $profilePostId,
      $premiums,
      $widget,
      $pcp,
      TRUE,
      $isAprovalNeeded
    );

    // logout
    $this->webtestLogout();

    $this->openCiviPage('contribute/transact', "reset=1&id=$pageId", "_qf_Main_upload-bottom");

    $this->click("xpath=//div[@class='crm-section other_amount-section']//div[2]/input");
    $this->type("xpath=//div[@class='crm-section other_amount-section']//div[2]/input", $contributionAmount);
    $this->type("email-5", $email);

    $this->webtestAddCreditCardDetails();
    $this->webtestAddBillingDetails($firstName, $middleName, $lastName);

    $this->click("_qf_Main_upload-bottom");
    $this->waitForPageToLoad($this->getTimeoutMsec());
    $this->waitForElementPresent("_qf_Confirm_next-bottom");
    $this->click("_qf_Confirm_next-bottom");

    $this->waitForElementPresent("thankyou_footer");
    $this->openCiviPage("contribute/campaign", "action=add&reset=1&pageId={$pageId}&component=contribute", "_qf_PCPAccount_next-bottom");

    $cmsUserName = 'CmsUser' . substr(sha1(rand()), 0, 7);
    $this->type("cms_name", $cmsUserName);
    $this->click("checkavailability");
    $this->type("first_name", $firstName);
    $this->type("last_name", $lastName);
    $this->type("email-Primary", $email);
    if ($this->isElementPresent("cms_pass")) {
      $pass = 'myBigPassword';
      $this->type("cms_pass", $pass);      
      $this->type("cms_confirm_pass", $pass);      
    }
//    sleep(20);
    $this->click("_qf_PCPAccount_next-bottom");

    $this->waitForElementPresent("_qf_Campaign_upload-bottom");

    $pcpTitle = 'PCPTitle' . substr(sha1(rand()), 0, 7);
    $this->type("pcp_title", $pcpTitle);
    $this->type("pcp_intro_text", "Welcome Text $hash");
    $this->type("goal_amount", $contributionAmount);
    $this->click("_qf_Campaign_upload-bottom");

    $this->webtestLogin();
    $this->openCiviPage("admin/pcp", "reset=1", "_qf_PCP_refresh");
    $this->select('status_id', 'value=1');
    $this->click("_qf_PCP_refresh");
    $this->waitForElementPresent("_qf_PCP_refresh");
    $id = explode('id=', $this->getAttribute("xpath=//div[@id='option11_wrapper']/table[@id='option11']/tbody/tr/td/a[text()='$pcpTitle']@href"));
    $pcpId = trim($id[1]);
    $pcpUrl = "civicrm/contribute/pcp/info?reset=1&id=$pcpId";
    $this->click("xpath=//td[@id=$pcpId]/span[1]/a[2]");
    $this->waitForPageToLoad($this->getTimeoutMsec());
    // logout
    $this->webtestLogout();

    // Set pcp contributor name
    $donorFirstName = 'Donor' . substr(sha1(rand()), 0, 4);
    $donorLastName = 'Person' . substr(sha1(rand()), 0, 7);
    $middleName = 'Mid' . substr(sha1(rand()), 0, 7);

    $this->open($this->sboxPath . $pcpUrl);

    $this->waitForPageToLoad($this->getTimeoutMsec());
    $this->openCiviPage("contribute/transact", "reset=1&id=$pageId&pcpId=$id[1]", "_qf_Main_upload-bottom");
    $this->click("xpath=//div[@class='crm-section other_amount-section']//div[2]/input");
    $this->type("xpath=//div[@class='crm-section other_amount-section']//div[2]/input", $contributionAmount);
    $this->type("email-5", $donorFirstName . "@example.com");

    $this->webtestAddCreditCardDetails();
    $this->webtestAddBillingDetails($donorFirstName, $middleName, $donorLastName);
    $this->click("_qf_Main_upload-bottom");
    $this->waitForPageToLoad($this->getTimeoutMsec());
    $this->waitForElementPresent("_qf_Confirm_next-bottom");
    $this->click("_qf_Confirm_next-bottom");

    $this->waitForElementPresent("thankyou_footer");

    //login to check contribution
    $this->webtestLogin();

    //Find Contribution
    $this->openCiviPage("contribute/search", "reset=1", "contribution_date_low");

    $this->select('contribution_pcp_made_through_id', "label={$pcpTitle}");

    $this->click("_qf_Search_refresh");

    $this->waitForPageToLoad($this->getTimeoutMsec());

    $this->waitForElementPresent("xpath=//div[@id='contributionSearch']//table//tbody/tr[1]/td[11]/span/a[text()='View']");
    $this->click("xpath=//div[@id='contributionSearch']//table//tbody/tr[1]/td[11]/span/a[text()='View']");
    $this->waitForPageToLoad($this->getTimeoutMsec());
    $this->waitForElementPresent("_qf_ContributionView_cancel-bottom");

    // View Contribution Record and test for expected values
    $expected = array(
      'From' => "{$donorFirstName} {$donorLastName}",
      'Financial Type' => 'Donation',
      'Total Amount' => $contributionAmount,
      'Contribution Status' => 'Completed',
    );
    $this->webtestVerifyTabularData($expected);

    //Check for SoftCredit
    $this->verifyText("xpath=id('PCPView')//div[@class='crm-accordion-body']/table/tbody/tr[1]/td[2]/a[text()]", preg_quote($pcpTitle));
    $this->verifyText("xpath=id('PCPView')//div[@class='crm-accordion-body']/table/tbody/tr[2]/td[2]/a[text()]", preg_quote("{$firstName} {$lastName}"));

    // Check PCP Summary Report
    $this->openCiviPage('report/instance/15', 'reset=1');
    $this->verifyText("PCP", preg_quote($pcpTitle));
    $this->verifyText("PCP", preg_quote("{$lastName}, {$firstName}"));
  }
}

