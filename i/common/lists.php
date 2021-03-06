<?php

function twitter_lists_tweets($user, $list) {
	// Tweets belonging to a list
	$api_options = array("owner_screen_name" => $user, "slug" => $list);
	$max_id = $_GET['max_id'];

	if (!is_numeric($max_id)) {
		$max_id = -1;
	}
	if ($max_id > 0) {
		$api_options["max_id"] = $max_id;
	}

	$list = execute_codebird("lists_statuses",$api_options);
	return $list;
}

function twitter_lists_user_lists($user) {
	// Lists a user has created
	$api_options = array("screen_name" => $user);
	$cursor = $_GET['cursor'];

	if (!is_numeric($cursor)) {
		$cursor = -1;
	}
	if ($cursor > 0) {
		$api_options["cursor"] = $cursor;
	}
	$api_options["count"] = setting_fetch('dabr_perPage', 20);
	$list = execute_codebird("lists_list",$api_options);
	return $list;
}

function twitter_lists_user_memberships($user) {
	// Lists a user belongs to
	$api_options = array("screen_name" => $user);
	$cursor = $_GET['cursor'];

	if (!is_numeric($cursor)) {
		$cursor = -1;
	}
	if ($cursor > 0) {
		$api_options["cursor"] = $cursor;
	}
	$api_options["count"] = setting_fetch('dabr_perPage', 20);
	$list = execute_codebird("lists_memberships",$api_options);
	return $list;
}

function twitter_lists_list_members($user, $list) {
	// Members of a list
	$api_options = array("owner_screen_name" => $user, "slug" => $list);
	$api_options["count"] = setting_fetch('dabr_perPage', 20);
	$cursor = $_GET['cursor'];

	if (!is_numeric($cursor)) {
		$cursor = -1;
	}
	if ($cursor > 0) {
		$api_options["cursor"] = $cursor;
	}
	$list = execute_codebird("lists_members",$api_options);
	return $list;
}

function twitter_lists_list_subscribers($user, $list) {
	// Subscribers of a list
	$api_options = array("owner_screen_name" => $user, "slug" => $list);
	$api_options["count"] = setting_fetch('dabr_perPage', 20);
	$cursor = $_GET['cursor'];

	if (!is_numeric($cursor)) {
		$cursor = -1;
	}
	if ($cursor > 0) {
		$api_options["cursor"] = $cursor;
	}
	$list = execute_codebird("lists_subscribers",$api_options);
	return $list;
}

/* Front controller for the new pages

List URLS:
lists -- current user's lists
lists/$user -- chosen user's lists
lists/$user/lists -- alias of the above
lists/$user/memberships -- lists user is in
lists/$user/$list -- tweets
lists/$user/$list/members
lists/$user/$list/subscribers
lists/$user/$list/edit -- rename a list (no member editting)
*/

function lists_controller($query) {
	//	Pick off $user from $query or default to the current user
	$user = $query[1];
	if (!$user) $user = user_current_username();

	//	Fiddle with the $query to find which part identifies the page they want
	if ($query[3]) {
		// URL in form: lists/$user/$list/$method
		$method = $query[3];
		$list = $query[2];
	} else {
		// URL in form: lists/$user/$method
		$method = $query[2];
	}

	// Attempt to call the correct page based on $method
	switch ($method) {
		case '':
		case 'lists':
			// Show which lists a user has created
			return lists_lists_page($user);
		case 'memberships':
			// Show which lists a user belongs to
			return lists_membership_page($user);
		case 'members':
			// Show members of a list
			return lists_list_members_page($user, $list);
		case 'subscribers':
			// Show subscribers of a list
			return lists_list_subscribers_page($user, $list);
		case 'edit':
			// TODO: List editting page (name and availability)
			break;
		default:
			// Show tweets in a particular list
			$list = $method;
			return lists_list_tweets_page($user, $list);
	}

	//	Error to be shown for any incomplete pages (breaks above)
	return theme('error', _(LIST_PAGE_NOT_FOUND));
}

/* Pages */

function lists_lists_page($user) {
 	// Show a user's lists
 	$lists = twitter_lists_user_lists($user);
 	$content = "<p>
						<a href='lists/{$user}/memberships'>".sprintf(_(LIST_FOLLOWING),$user)."</a> | ".
						"<strong>".sprintf(_(LIST_USER_FOLLOWS),$user)."</strong>".
					"</p>";
 	$content .= theme('lists', $lists);
 	theme('page', sprintf(_(USERS_LISTS_TITLE),$user), $content);
}

function lists_membership_page($user) {
	// Show lists a user belongs to
	$lists = twitter_lists_user_memberships($user);
	$content = "<p>
						<strong>".sprintf(_(LIST_FOLLOWING),$user)."</strong> | ".
						"<a href='lists/{$user}'>".sprintf(_(LIST_USER_FOLLOWS),$user)."</a>".
					"</p>";
	$content .= theme('lists', $lists);
	theme('page', _(MEMBERSHIP_LISTS_TITLE), $content);
}

function lists_list_tweets_page($user, $list) {
	// Show tweets in a list
	$tweets = twitter_lists_tweets($user, $list);
	$tl = twitter_standard_timeline($tweets, 'user');
	$content = theme('status_form');
	$list_url = "lists/{$user}/{$list}";
	$content .= "<p>"._(LIST_TWEETS_IN)." <a href='{$user}'>@{$user}</a>/<strong>{$list}</strong> | ".
						"<a href='{$list_url}/members'>"    ._(LIST_VIEW_MEMBERS)    ."</a> | ".
						"<a href='{$list_url}/subscribers'>"._(LIST_VIEW_SUBSCRIBERS)."</a>".
					"</p>";
	$content .= theme('timeline', $tl);
	theme('page', "{$user}/{$list}", $content);
}

function lists_list_members_page($user, $list) {
	// Show members of a list
	// TODO: add logic to CREATE and REMOVE members
	$p = twitter_lists_list_members($user, $list);

	// TODO: use a different theme() function? Add a "delete member" link for each member
	$list_url = "<a href='{$user}'>@{$user}</a>/<a href='lists/{$user}/{$list}'>{$list}</a>";
	$content = "<h2>".sprintf(_(LIST_MEMBERS),$list_url).":</h2>\n";
	$content .= theme('users_list', $p);
	theme('page', sprintf(_(LIST_MEMBERS),"{$user}/{$list}"), $content);
}

function lists_list_subscribers_page($user, $list) {
	// Show subscribers of a list
	$p = twitter_lists_list_subscribers($user, $list);
	$list_url = "<a href='{$user}'>@{$user}</a>/<a href='lists/{$user}/{$list}'>{$list}</a>";

	$content = "<h2>".sprintf(_(LIST_SUBSCRIBERS),$list_url).":</h2>\n";
	$content .= theme('users_list', $p);
	theme('page', sprintf(_(LIST_SUBSCRIBERS),"{$user}/{$list}"), $content);
}

/* Theme functions */

function theme_lists($json) {
	if(isset($json->lists)) {
		$lists = $json->lists;
	}
	else {
		$lists = $json;
	}
	if (sizeof($lists) == 0 || $lists == '[]') {
		return "<p>"._(NO_LISTS)."p>";
	}
	$rows = array();
	$headers_html = '<div class="table">
								<div class="table-row">
									<span class="table-cell">'._(LISTS).'</span>
									<span class="table-cell-middle">'._(MEMBERS).'</span>
									<span class="table-cell-end">'._(SUBSCRIBERS).'</span>
								</div>';
	$headers = array($headers_html);
	foreach ($lists as $list) {
		$url = "lists/{$list->user->screen_name}/{$list->slug}";

		$rows[] = array('data' => 	array(
										array('data' => "<a href='{$list->user->screen_name}'>@{$list->user->screen_name}</a>/<wbr><a href='{$url}'><strong>{$list->slug}</strong></a>", 'style' => 'display: table-cell;'),
										array('data' => "<a href='{$url}/members'>".number_format($list->member_count)."</a>", 'class' => 'table-cell-middle'),
										array('data' => "<a href='{$url}/subscribers'>".number_format($list->subscriber_count)."</a>", 'class' => 'table-cell-end'),
	                                ),
		                'class' => 'table-row');
	}

	$content = theme('table', $headers, $rows);
	$content .= theme('list_pagination', $json);
	return $content;
}
