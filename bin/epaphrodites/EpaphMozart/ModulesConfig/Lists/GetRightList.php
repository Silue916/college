<?php

namespace Epaphrodites\epaphrodites\EpaphMozart\ModulesConfig\Lists;

use Epaphrodites\epaphrodites\constant\epaphroditeClass;

class GetRightList extends epaphroditeClass
{

   /** users right list
    * 
    * @return array
    */
   public static function RightList():array
   {

      return [
         [
            'apps' => 'profil',
            'libelle' => "Change password",
            'path' => 'usersFolder/change_password'
         ],
         [
            'apps' => 'profil',
            'libelle' => "Change my informations",
            'path' => 'usersFolder/edit_users_infos'
         ],
         [
            'apps' => 'users',
            'libelle' => 'Import system users',
            'path' => 'usersFolder/import_users',
         ], 
         [
            'apps' => 'users',
            'libelle' => 'List of system all Users',
            'path' => 'usersFolder/all_users_list',
         ], 
         [
            'apps' => 'students',
            'libelle' => 'Manage students',
            'path' => 'studentsFolder/manage_students',
         ],
         [
            'apps' => 'actions',
            'libelle' => 'List of recent actions',
            'path' => 'settingFolder/list_of_recent_actions',
         ],           

        ];
    }
 }