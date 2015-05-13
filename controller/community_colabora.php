<?php

/*
 * This file is part of FacturaSctipts
 * Copyright (C) 2015  Carlos Garcia Gomez  neorazorx@gmail.com
 *
 * This program is free software: you can redistribute it and/or modify
 * it under the terms of the GNU Affero General Public License as
 * published by the Free Software Foundation, either version 3 of the
 * License, or (at your option) any later version.
 *
 * This program is distributed in the hope that it will be useful,
 * but WITHOUT ANY WARRANTY; without even the implied warranty of
 * MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
 * GNU Affero General Public License for more details.
 * 
 * You should have received a copy of the GNU Affero General Public License
 * along with this program.  If not, see <http://www.gnu.org/licenses/>.
 */

require_model('comm3_item.php');
require_model('comm3_visitante.php');

/**
 * Description of community_home
 *
 * @author carlos
 */
class community_colabora extends fs_controller
{
   public $page_title;
   public $page_description;
   public $resultados;
   public $rid;
   public $visitante;
   public $visitante_s;
   
   public function __construct()
   {
      parent::__construct(__CLASS__, 'Visitantes', 'comunidad', FALSE, TRUE);
   }
   
   protected function private_core()
   {
      $visitante = new comm3_visitante();
      
      if( isset($_GET['nuevo_email']) )
      {
         if( filter_var($_GET['nuevo_email'], FILTER_VALIDATE_EMAIL) )
         {
            $visitante->email = $_GET['nuevo_email'];
            $visitante->rid = '-1';
            $visitante->autorizado = $this->user->nick;
            $visitante->perfil = 'cliente';
            
            if( $visitante->exists() )
            {
               $this->new_error_msg('El email ya está asignado.');
            }
            else if( $visitante->save() )
            {
               header( 'Location: '.$visitante->url() );
            }
            else
               $this->new_error_msg('Error al guardar los datos.');
         }
         else
            $this->new_error_msg('Email no válido.');
         
         if($this->user->admin)
         {
            $this->resultados = $visitante->all();
         }
         else
         {
            $this->resultados = $visitante->all_for_user($this->user->nick);
         }
      }
      else if( isset($_REQUEST['email']) )
      {
         $this->template = 'community_colabora2';
         $this->visitante_s = $visitante->get($_REQUEST['email']);
         
         if( isset($_POST['perfil']) )
         {
            if($this->user->admin OR $this->visitante_s->autorizado == $this->user->nick)
            {
               $this->visitante_s->perfil = $_POST['perfil'];
               $this->visitante_s->privado = isset($_POST['privado']);
               
               $this->visitante_s->nick = NULL;
               if($_POST['nick'] != '')
               {
                  $this->visitante_s->nick = $_POST['nick'];
               }
               
               $this->visitante_s->autorizado = NULL;
               if($_POST['autorizado'] != '')
               {
                  $this->visitante_s->autorizado = $_POST['autorizado'];
               }
               
               if( $this->visitante_s->save() )
               {
                  $this->new_message('Datos guardados correctamente.');
               }
               else
                  $this->new_error_msg('Error al guardar los datos.');
            }
            else
               $this->new_error_msg('No estás autorizado.');
         }
         
         if(!$this->visitante_s)
         {
            $item = new comm3_item();
            $this->resultados = $item->all_by_email($_REQUEST['email']);
         }
         else if($this->user->admin OR $this->visitante_s->autorizado == $this->user->nick)
         {
            $item = new comm3_item();
            $this->resultados = $item->all_by_email($_REQUEST['email']);
         }
         else
         {
            $this->new_error_msg('No tienes permiso para ver estos datos.');
            $this->template = 'community_colabora';
            $this->resultados = $visitante->all_for_user($this->user->nick);
         }
      }
      else if( isset($_GET['delete']) )
      {
         $vis = $visitante->get($_GET['delete']);
         if($vis)
         {
            if(!$this->user->admin AND $vis->autorizado != $this->user->nick)
            {
               $this->new_error_msg('No tienes permiso para eliminar estos datos.');
            }
            else if( $vis->delete() )
            {
               $this->new_message('Visitante eliminado correctamente.');
            }
            else
               $this->new_error_msg('Error al eliminar el visitante.');
         }
         else
            $this->new_error_msg('Visitante no encontrado.');
         
         if($this->user->admin)
         {
            $this->resultados = $visitante->all();
         }
         else
         {
            $this->resultados = $visitante->all_for_user($this->user->nick);
         }
      }
      else
      {
         if($this->user->admin)
         {
            $this->resultados = $visitante->all();
         }
         else
         {
            $this->resultados = $visitante->all_for_user($this->user->nick);
         }
      }
   }
   
   protected function public_core()
   {
      $this->page_title = 'Colabora &lsaquo; Comunidad FacturaScripts';
      $this->page_description = 'Colabora en el desarrollo de FacturaScripts, forma parte de la comunidad.';
      $this->template = 'public/colabora';
      $visit0 = new comm3_visitante();
      $this->visitante = FALSE;
      
      $this->rid = $this->random_string(30);
      if( isset($_COOKIE['rid']) )
      {
         $this->rid = $_COOKIE['rid'];
         $this->visitante = $visit0->get_by_rid($this->rid);
      }
      else
      {
         setcookie('rid', $this->rid, time()+FS_COOKIES_EXPIRE, '/');
      }
      
      if( isset($_POST['humanity']) )
      {
         if($_POST['humanity'] == '')
         {
            if($this->visitante)
            {
               $this->visitante->email = $_POST['email'];
               $this->visitante->perfil = $_POST['perfil'];
               if( $this->visitante->save() )
               {
                  $this->new_message('Datos guardados correctamente.');
               }
               else
                  $this->new_error_msg('Error al guardar los datos.');
            }
            else
            {
               $this->visitante = new comm3_visitante();
               $this->visitante->rid = $this->rid;
               $this->visitante->email = $_POST['email'];
               $this->visitante->perfil = $_POST['perfil'];
               
               if( isset($_SERVER['REMOTE_ADDR']) )
               {
                  $this->visitante->last_ip = $_SERVER['REMOTE_ADDR'];
               }
               
               if( isset($_SERVER['HTTP_USER_AGENT']) )
               {
                  $this->visitante->last_browser = $_SERVER['HTTP_USER_AGENT'];
               }
               
               if( $this->visitante->save() )
               {
                  $this->new_message('Datos guardados correctamente.');
               }
               else
                  $this->new_error_msg('Error al guardar los datos.');
            }
         }
         else
         {
            $this->new_error_msg('Tienes que borrar el número para demostrar que eres humano.');
         }
      }
      
      $item = new comm3_item();
      $this->resultados = $item->all_by_rid($this->rid);
   }
   
   public function path()
   {
      if( defined('COMM3_PATH') )
      {
         return COMM3_PATH;
      }
      else
         return '';
   }
}
