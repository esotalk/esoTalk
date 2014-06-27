<?php
// Copyright 2011 Toby Zerner, Simon Zerner
// This file is part of esoTalk. Please see the included license file for usage information.

if (!defined("IN_ESOTALK")) exit;

/**
 * The members controller handles the member list, online members sheet, and the creation of new members.
 *
 * @package esoTalk
 */
class ETMembersController extends ETController {


/**
 * Show the member list page.
 *
 * @param string $orderBy What to sort the members by.
 * @param mixed $start Where to start the results from. This can be:
 * 		- An integer, in which case it will be used as a numerical offset.
 * 		- pX, where X is the "page" number.
 * 		- A letter to start from, if $orderBy is "name".
 * @return void
 */
public function action_index($orderBy = false, $start = 0)
{
	if (!$this->allowed("esoTalk.members.visibleToGuests")) return;

	// Begin constructing a query to fetch results.
	$sql = ET::SQL()->from("member m");

	// If we've limited results by a search string...
	if ($searchString = R("search")) {

		// Explode separate terms by the plus character.
		$terms = explode("+", $searchString);

		// Get an array of all groups which we can possibly filter by.
		$groups = ET::groupModel()->getAll();
		$groups[GROUP_ID_ADMINISTRATOR] = array("name" => ACCOUNT_ADMINISTRATOR);
		$groups[GROUP_ID_MEMBER] = array("name" => ACCOUNT_MEMBER);
		$groups[GROUP_ID_GUEST] = array("name" => ACCOUNT_SUSPENDED);

		$conditions = array();

		$this->trigger("parseTerms", array(&$terms, $sql, &$conditions));

		foreach ($terms as $k => $term) {

			$term = mb_strtolower(trim($term), "UTF-8");

			if (!$term) continue;

			// If the search string matches the start of any group names, then we'll filter members by their account/group.
			$group = false;
			foreach ($groups as $id => $g) {
				$name = $g["name"];
					if (strpos(mb_strtolower(T("group.$name", $name), "UTF-8"), $term) === 0
					or strpos(mb_strtolower(T("group.$name.plural", $name), "UTF-8"), $term) === 0) {
					$group = $id;
					break;
				}
			}

			// Did we find any matching groups just before? If so, add a WHERE condition to the query to filter by group.
			if ($group !== false) {
				if ($group < 0) {
					$conditions[] = "account=:account$k";
					$sql->bind(":account$k", $groups[$group]["name"]);
				}
				elseif (!$groups[$group]["private"] or ET::groupModel()->groupIdsAllowedInGroupIds(ET::$session->getGroupIds(), $group, true)) {
					$sql->from("member_group mg", "mg.memberId=m.memberId", "left");
					$conditions[] = "mg.groupId=:group$k";
					$sql->bind(":group$k", $group);
				}
			}

			// Also perform a normal LIKE search.
			$conditions[] = "username LIKE :search$k";
			$sql->bind(":search$k", "%".$term."%");

		}

		$sql->where(implode(" OR ", $conditions));
	}

	// Create a query to get the total number of results. Clone the results one to retain the same WHERE conditions.
	$count = clone $sql;
	$count = $count
		->select("COUNT(m.memberId)")
		->exec()
		->result();

	// Make an array of possible orders for the list.
	$orders = array(
		"name" => array(T("Name"), "username ASC"),
		"posts" => array(T("Posts"), "countPosts DESC"),
		"activity" => array(T("Last active"), "lastActionTime DESC")
	);

	// If an invalid orderBy key was provided, just use the first one.
	if (!isset($orders[$orderBy])) $orderBy = reset(array_keys($orders));

	// Work out where to start the results from.
	$page = 0;
	if ($start) {

		// If we're ordering by name and the start argument is a single letter...
		if ($orderBy == "name" and strlen($start) == 1 and ctype_alpha($start)) {

			// Run a query to get the position of the first member starting with this letter.
			$start = ET::SQL()
				->select("COUNT(memberId)", "position")
				->from("member")
				->where("STRCMP(username, :username) = -1")
				->bind(":username", $start)
				->exec()
				->result();
			$start = min($count - C("esoTalk.members.membersPerPage"), $start);

		}

		// If the start argument is "pX", where X is the page number...
		elseif ($start[0] == "p") {
			$page = ltrim($start, "p");
			$start = C("esoTalk.members.membersPerPage") * ($page - 1);
		}

		// Otherwise, parse the start argument as a simple integer offset.
		else {
			$start = (int)($start);
			$page = round($start / C("esoTalk.members.membersPerPage"));
		}

		// Apply the offset to the query.
		$start = max(0, $start);
		$sql->offset($start);
	}

	// Finish constructing the query. We want to get a list of member IDs to show as the results.
	$ids = $sql
		->select("m.memberId")
		->limit(C("esoTalk.members.membersPerPage"))
		->orderBy($orders[$orderBy][1])
		->exec()
		->allRows();
	foreach ($ids as &$id) $id = $id["memberId"];

	// Finally, fetch the member data for the members with these IDs.
	if ($ids) $members = ET::memberModel()->getByIds($ids);
	else $members = array();

	// If we're ordering by last active, filter out members who have opted out of being displayed on the online list.
	if ($orderBy == "activity") {
		foreach ($members as $k => $member) {
			if (!empty($member["preferences"]["hideOnline"])) {
				unset($members[$k]);
			}
		}
	}

	// If we're doing a normal page load...
	if ($this->responseType === RESPONSE_TYPE_DEFAULT) {

		// Set the title and make sure this page isn't indexed.
		$this->title = T("Member List");
		$this->addToHead("<meta name='robots' content='noindex, noarchive'/>");

		// Work out the canonical URL for this page.
		$url = "members/$orderBy/p$page";
		$this->canonicalURL = URL($url, true);
		$this->pushNavigation("members", "members", URL($url));

		// Add JavaScript files and variables for the page to use.
		$this->addJSFile("core/js/scrubber.js");
		$this->addJSFile("core/js/members.js");
		$this->addJSVar("membersPerPage", C("esoTalk.members.membersPerPage"));
		$this->addJSVar("countMembers", $count);
		$this->addJSVar("startFrom", $start);
		$this->addJSVar("searchString", $searchString);
		$this->addJSVar("orderBy", $orderBy);
		$this->addJSLanguage("Sort By");

		// Add the default gambits to the gambit cloud: gambit text => css class to apply.
		$gambits = array(
			"groups" => array(
				T("group.administrator.plural") => array("gambit-group-administrator", "icon-wrench"),
				T("group.member.plural") => array("gambit-group-member", "icon-user"),
				T("group.suspended") => array("gambit-group-suspended", "icon-shield")
			)
		);

		$groups = ET::groupModel()->getAll();
		foreach ($groups as $group) {
			if ($group["private"]) continue;
			$name = $group["name"];
			addToArrayString($gambits["groups"], T("group.$name.plural", ucfirst($name)), array("gambit-group-$name", "icon-tag"), 1);
		}

		$this->trigger("constructGambitsMenu", array(&$gambits));

		// Construct the gambits menu based on the above arrays.
		$gambitsMenu = ETFactory::make("menu");
		$linkPrefix = "members/".$orderBy."/?search=";

		foreach ($gambits as $section => $items) {
			foreach ($items as $gambit => $classes) {
				$gambitsMenu->add($classes[0], "<a href='".URL($linkPrefix.urlencode($gambit))."' class='{$classes[0]}' data-gambit='$gambit'>".(!empty($classes[1]) ? "<i class='{$classes[1]}'></i> " : "")."$gambit</a>");
			}
			end($gambits);
			if ($section !== key($gambits)) $gambitsMenu->separator();
		}

		$this->data("gambitsMenu", $gambitsMenu);
	}

	// Pass data to the view.
	$this->data("members", $members);
	$this->data("countMembers", $count);
	$this->data("startFrom", $start);
	$this->data("searchString", $searchString);
	$this->data("orders", $orders);
	$this->data("orderBy", $orderBy);

	// On an AJAX request, just render the list, and also pass back the startFrom position.
	if ($this->responseType === RESPONSE_TYPE_AJAX) {
		$this->json("startFrom", $start);
		$this->render("members/list");
	}

	else $this->render("members/index");
}


/**
 * Show the "create member" sheet, containing a form to create a new member.
 *
 * @return void
 */
public function action_create()
{
	// Non-admins can't do this! Suckers.
	if (!ET::$session->isAdmin()) return;

	// Set up the form.
	$form = ETFactory::make("form");
	$form->action = URL("members/create");

	// Was the cancel button pressed?
	if ($form->isPostBack("cancel"))
		$this->redirect(URL(R("return", "members")));

	// Was the "create" button pressed?
	if ($form->validPostBack("submit")) {

		// Make sure the passwords match.
		if ($form->getValue("confirm") != $form->getValue("password"))
			$form->error("confirm", T("message.passwordsDontMatch"));

		// If there were no preliminary errors, proceed to attempt to create the member with the model.
		if (!$form->errorCount()) {

			$data = array(
				"username"  => $form->getValue("username"),
				"email"     => $form->getValue("email"),
				"password"  => $form->getValue("password"),
				"account"   => ACCOUNT_MEMBER,
				"confirmed" => true
			);

			$model = ET::memberModel();
			$id = $model->create($data);

			// If there were any errors, pass them back to the form.
			if ($model->errorCount()) $form->errors($model->errors());

			// Otherwise, redirect to this new member's profile.
			else $this->redirect(URL(memberURL($id, $form->getValue("username"))));

		}

	}

	$this->data("form", $form);
	$this->render("members/create");
}


/**
 * Show the "online members" sheet.
 *
 * @return void
 */
public function action_online()
{
	// Check if we have permission to view the online list.
	if (!C("esoTalk.members.visibleToGuests") and !ET::$session->user) {
		$this->render404(T("message.pageNotFound"));
		return false;
	}

	// Set the title and make sure this page isn't indexed.
	$this->title = T("Online Members");
	$this->addToHead("<meta name='robots' content='noindex, noarchive'/>");

	// Construct a query to get only members who are online.
	$sql = ET::SQL()
		->where((time() - ET::config("esoTalk.userOnlineExpire"))."<lastActionTime")
		->orderBy("lastActionTime DESC");

	// Pass this query to the member model and get all of these members' data.
	$members = ET::memberModel()->getWithSQL($sql);

	// Filter out members who have opted out of being displayed on the online list.
	$hidden = 0;
	foreach ($members as $k => $member) {
		if (!empty($member["preferences"]["hideOnline"])) {
			unset($members[$k]);
			$hidden++;
		}
	}

	$this->data("members", $members);
	$this->data("hidden", $hidden);
	$this->render("members/online");
}


/**
 * Return a JSON array of up to 50 members whose usernames match a given string. This data can be used to
 * create a list of members in an autocomplete menu.
 *
 * @param string $input The string to match member usernames against.
 * @return void
 */
public function action_autocomplete($input = "")
{
	// Force the response type to JSON.
	$this->responseType = RESPONSE_TYPE_JSON;

	// Don't do this for strings less than three characters for performance reasons.
	if (strlen($input) < 2) return;

	// Construct a query to fetch matching members.
	$results = ET::SQL()
		->select("'member' AS type")
		->select("memberId")
		->select("username AS name")
		->select("avatarFormat")
		->select("email")
		->from("member")
		->where("username LIKE :username")
		->bind(":username", $input."%")
		->orderBy("username")
		->limit(50)
		->exec()
		->allRows();

	// Loop through the results and generate avatar HTML for each one.
	foreach ($results as $k => $v) {
		$results[$k]["avatar"] = avatar($v, "thumb");
		unset($results[$k]["avatarFormat"]);
		unset($results[$k]["email"]);

		// Convert spaces in the member name to non-breaking spaces.
		$results[$k]["name"] = str_replace(" ", "\xc2\xa0", $results[$k]["name"]);
	}

	$this->json("results", $results);
	$this->render();
}

}
