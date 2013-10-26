<?php

/**
 * This file is part of SMF 2 antispam
 * 
 * Copyright (c) 2013, Manatsawin Hanmongkolchai
 * All rights reserved.
 * 
 * Redistribution and use in source and binary forms, with or without
 * modification, are permitted provided that the following conditions are met:
 *    * Redistributions of source code must retain the above copyright
 *      notice, this list of conditions and the following disclaimer.
 *    * Redistributions in binary form must reproduce the above copyright
 *      notice, this list of conditions and the following disclaimer in the
 *      documentation and/or other materials provided with the distribution.
 *    * Neither the name of the contributors may be used to endorse or
 *      promote products derived from this software without specific
 *      prior written permission.
 **/

require_once '../SSI.php';

// use theme output
$ssi_on_error_method = true;
$context['page_title'] = 'Anti-spam configuration';
$context['page_title_html_safe'] = 'Anti-spam configuration';

if(!$context['user']['is_logged'] || !in_array($user_info['groups'][0], array(1, 2))){
	header('HTTP/1.0 401 Unauthorized');
	fatal_error('Protected access');
}

template_header();
?>
<h1>Anti-spam configuration</h1>
<div class="topic_table" ng-app="antispam" ng-controller="MessageList">
	<table class="table_grid" cellspacing="0">
		<thead>
			<tr class="catbg">
				<th style="min-width: 40%;">Message</th>
				<th>Created by</th>
				<th>Created</th>
				<th>Last modified by</th>
				<th>Last modified</th>
				<th style="width: 20px;">Del</th>
			</tr>
		</thead>
		<tbody>
			<tr ng-repeat="kw in data">
				<td class="windowbg" ng-dblclick="edit = !edit; kw.edited='Not saved'">
					<form ng-submit="saveEdit()" ng-show="edit">
						<input ng-model="kw.text" style="width: 90%;" autofocus required>
					</form>
					<div ng-show="!edit" ng-bind="kw.text"></div>
				</td>
				<td class="windowbg"><a ng-if="kw.created_by" ng-href="../index.php?action=profile&amp;u={{kw.created_by.id_member}}" ng-bind="kw.created_by.member_name"></a></td>
				<td class="windowbg" ng-bind="kw.created"></td>
				<td class="windowbg"><a ng-href="../index.php?action=profile&amp;u={{kw.edited_by.id_member}}" ng-bind="kw.edited_by.member_name"></a></td>
				<td class="windowbg" ng-bind="kw.edited"></td>
				<td class="windowbg"><button class="button_submit" ng-click="delete(kw.id)">&times;</button></td>
			</tr>
		</tbody>
	</table>
	<form ng-submit="add()">
		<label>
			Message:
			<input type="text" ng-model="newItem" style="width: 80%;" autofocus required>
		</label>
		<input type="submit" class="button_submit" value="Save">
	</form>
	Double click message text in table to edit.
</div>
<script src="https://ajax.googleapis.com/ajax/libs/angularjs/1.0.8/angular.min.js"></script>
<script>
!function(){
	'use strict';
	if(!Array.prototype.forEach || !window['JSON']){
		alert('This browser does not provide required runtimes.');
	}
	angular.module('antispam', [])
		.factory('antihack', function(){
			return function(msg){
				return msg.replace("for\(;;\);\n", '');
			}
		})
		.controller('MessageList', ['antihack', '$scope', '$http', function(antihack, $scope, $http){
			$scope.data = [];
			$scope.loading = true;
			$scope.newItem = "";
			$http({
				method: 'GET',
				url: 'server.php?act=get',
			}).success(function(data){
				$scope.data = JSON.parse(antihack(data));
			}).error(function(data){
				alert('Cannot load data:\n\n'+data);
			});

			$scope.delete = function(id){
				$scope.data.forEach(function(val){
					if(val.id == id){
						val.created = 'Deleting...';
					}
				});
				$http({
					method: 'DELETE', url: 'server.php?act=delete',
					data: JSON.stringify({
						'id': id
					})
				}).success(function(data){
					$scope.data = JSON.parse(antihack(data));
				}).error(function(data){
					alert('Cannot load data:\n\n'+data);
				});
			};

			$scope.saveEdit = function(evt){
				$http({
					method: 'POST', url: 'server.php?act=edit',
					data: JSON.stringify({
						'id': this.kw.id,
						'text': this.kw.text
					})
				}).success(function(data){
					$scope.data = JSON.parse(antihack(data));
				}).error(function(data){
					alert('Cannot load data:\n\n'+data);
				});
			}

			$scope.add = function(){
				$scope.data.push({
					'text': $scope.newItem,
					'created': 'Saving...',
					'created_by': null
				});
				$http({
					method: 'PUT', url: 'server.php?act=save',
					data: JSON.stringify({
						'text': $scope.newItem
					})
				}).success(function(data){
					$scope.data = JSON.parse(antihack(data));
				}).error(function(data){
					alert('Cannot load data:\n\n'+data);
				});
				$scope.newItem = '';
			}
		}]);
}();
</script>
<?php
template_footer();