(function() {
	if ($.inArray(mw.config.get('wgCanonicalSpecialPageName'), ['Recentchanges', 'Watchlist']) === -1) {
		return;
	}
	$(".mw-usertoollinks").before('<a href="#" class="toggle-usertoollinks" title="Показване на допълнителните потребителски препратки">+</a>').hide();
	$('body').on('click', '.toggle-usertoollinks', function() {
		$(this).next().show().end().remove();
		return false;
	});

	var userNameToClass = function(userName) {
		return 'mw-user-'+userName.replace(/[ .]/g, '_');
	};

	var userNames = {};
	$('.mw-changeslist').find('.mw-userlink').each(function() {
		var userName = $(this).text();
		if (!userNames[userName]) {
			userNames[userName] = 0;
		}
		userNames[userName]++;
		var $parentTable = $(this).closest('table.mw-enhanced-rc').addClass(userNameToClass(userName));
		if ($parentTable.is('.mw-collapsible') && $(this).parent().is('.changedby')) {
			userNames[userName]--;
		}
	});

	var userNameItems = [];
	$.each(userNames, function(userName, editCount) {
		userNameItems.push('<li><a href="#" class="show-user-edits" title="Показване само на приносите на '+userName+'" data-user="'+userName+'" style="font-size:'+(1+editCount*0.1-0.1)+'em">'+userName+'</a> ×'+editCount+'</li>');
	});
	var $toggleLinksContainer = $('<ul class="hlist show-user-edits-container">'+userNameItems.join('')+'</ul>').insertBefore('.mw-changeslist');

	$('body').on('click', '.show-user-edits', function() {
		if ($(this).is('.active')) {
			$('table.mw-enhanced-rc').show();
		} else {
			var userName = $(this).data('user');
			$('table.mw-enhanced-rc').hide();
			$('.'+userNameToClass(userName)).show();
		}
		$(this).siblings().removeClass('active').end().toggleClass('active');
		return false;
	});

	$('html, body').animate({
		scrollTop: $toggleLinksContainer.offset().top
	}, 1000);
}());
