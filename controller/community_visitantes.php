<?php

/*
 * This file is part of FacturaSctipts
 * Copyright (C) 2015-2016  Carlos Garcia Gomez  neorazorx@gmail.com
 *
 * This program is free software: you can redistribute it and/or modify
 * it under the terms of the GNU Lesser General Public License as
 * published by the Free Software Foundation, either version 3 of the
 * License, or (at your option) any later version.
 *
 * This program is distributed in the hope that it will be useful,
 * but WITHOUT ANY WARRANTY; without even the implied warranty of
 * MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
 * GNU Lesser General Public License for more details.
 * 
 * You should have received a copy of the GNU Lesser General Public License
 * along with this program.  If not, see <http://www.gnu.org/licenses/>.
 */

require_once 'extras/phpmailer/class.phpmailer.php';
require_once 'extras/phpmailer/class.smtp.php';
require_once __DIR__.'/community_home.php';
require_model('comm3_plugin_key.php');

/**
 * Description of community_visitors
 *
 * @author carlos
 */
class community_visitantes extends community_home
{
   public $allow_delete;
   public $autorizados;
   public $claves;
   public $filtro_query;
   public $filtro_perfil;
   public $filtro_codpais;
   public $filtro_provincia;
   public $filtro_ciudad;
   public $filtro_compras;
   public $filtro_orden;
   public $offset;
   public $resultados;
   public $tab;
   public $visitante_s;
   
   public function __construct()
   {
      parent::__construct(__CLASS__, 'Clientes', 'comunidad');
   }
   
   protected function private_core()
   {
      parent::private_core();
      
      /// ¿El usuario tiene permiso para eliminar en esta página?
      $this->allow_delete = $this->user->allow_delete_on(__CLASS__);
      
      $this->share_extension();
      
      $this->offset = 0;
      if( isset($_GET['offset']) )
      {
         $this->offset = intval($_GET['offset']);
      }
      
      $this->filtro_query = '';
      $this->filtro_perfil = '---';
      $this->filtro_codpais = '---';
      $this->filtro_provincia = '---';
      $this->filtro_ciudad = '---';
      $this->filtro_compras = '---';
      $this->filtro_orden = 'last_login DESC';
      
      $this->tab = isset($_REQUEST['snick']);
      
      $this->visitante_s = FALSE;
      $visitante = new comm3_visitante();
      if( isset($_GET['nuevo_email']) )
      {
         /// nuevo visitante / cliente de partner
         if( filter_var($_GET['nuevo_email'], FILTER_VALIDATE_EMAIL) )
         {
            $visitante->email = $_GET['nuevo_email'];
            $visitante->rid = $this->random_string();
            $visitante->autorizado = $this->user->nick;
            $visitante->perfil = 'cliente';
            $visitante->privado = TRUE;
            
            if($visitante->email == $this->user->email)
            {
               $visitante->nick = $this->user->nick;
               $visitante->perfil = 'partner';
            }
            else if( isset($_REQUEST['snick']) )
            {
               $visitante->nick = $_REQUEST['snick'];
            }
            
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
         
         $this->resultados = $visitante->search_for_user($this->user->admin, $this->user->nick);
      }
      else if( isset($_REQUEST['email']) OR isset($_REQUEST['snick']) )
      {
         /// ver/modificar visitante
         $this->template = 'community_visitante';
         if($this->tab)
         {
            $this->template = 'community_visitante_tab';
         }
         
         if( isset($_REQUEST['email']) )
         {
            $this->visitante_s = $visitante->get($_REQUEST['email']);
         }
         else if( isset($_REQUEST['snick']) )
         {
            $this->visitante_s = $visitante->get_by_nick($_REQUEST['snick']);
         }
         
         if($this->visitante_s)
         {
            if($this->visitante_s->nick == $this->user->nick)
            {
               $this->allow_delete = FALSE;
            }
            
            $this->autorizados = array();
            
            if( isset($_POST['perfil']) )
            {
               if($this->user->admin OR $this->visitante_s->autorizado($this->user->nick) )
               {
                  if($this->user->admin)
                  {
                     $this->visitante_s->perfil = $_POST['perfil'];
                     
                     $this->visitante_s->nick = NULL;
                     if($_POST['nick'] != '')
                     {
                        $this->visitante_s->nick = $_POST['nick'];
                     }
                  }
                  
                  $this->visitante_s->privado = isset($_POST['privado']);
                  
                  $this->visitante_s->autorizado = NULL;
                  if($_POST['autorizado'] != '')
                  {
                     $this->visitante_s->autorizado = $_POST['autorizado'];
                  }
                  
                  $this->visitante_s->autorizado2 = NULL;
                  if($_POST['autorizado2'] != '')
                  {
                     $this->visitante_s->autorizado2 = $_POST['autorizado2'];
                  }
                  
                  $this->visitante_s->autorizado3 = NULL;
                  if($_POST['autorizado3'] != '')
                  {
                     $this->visitante_s->autorizado3 = $_POST['autorizado3'];
                  }
                  
                  $this->visitante_s->autorizado4 = NULL;
                  if($_POST['autorizado4'] != '')
                  {
                     $this->visitante_s->autorizado4 = $_POST['autorizado4'];
                  }
                  
                  $this->visitante_s->autorizado5 = NULL;
                  if($_POST['autorizado5'] != '')
                  {
                     $this->visitante_s->autorizado5 = $_POST['autorizado5'];
                  }
                  
                  $this->visitante_s->observaciones = $_POST['observaciones'];
                  
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
            
            if( $this->user->admin OR $this->visitante_s->autorizado($this->user->nick) )
            {
               $item = new comm3_item();
               $this->resultados = $item->all_by_visitante($this->visitante_s, $this->offset);
               $this->autorizados = $this->visitante_s->search_for_user(FALSE, $this->visitante_s->nick);
            }
            else
            {
               $this->new_error_msg('No tienes permiso para ver estos datos.');
               
               if($this->tab)
               {
                  $this->template = 'community_visitante_tab';
                  $this->visitante_s = FALSE;
               }
               else
               {
                  $this->template = 'community_visitantes';
                  $this->resultados = $visitante->search_for_user($this->user->admin, $this->user->nick);
               }
            }
            
            if($this->visitante_s)
            {
               $plk0 = new comm3_plugin_key();
               $this->claves = $plk0->all_from_email($this->visitante_s->email);
            }
         }
         else
         {
            $this->new_error_msg('Visitante no encontrado.');
            if($this->tab)
            {
               $this->template = 'community_visitante_tab';
            }
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
         
         $this->resultados = $visitante->search_for_user($this->user->admin, $this->user->nick);
      }
      else if( isset($_POST['filtro_query']) )
      {
         $this->filtro_query = $_POST['filtro_query'];
         $this->filtro_perfil = $_POST['filtro_perfil'];
         $this->filtro_codpais = $_POST['filtro_codpais'];
         $this->filtro_provincia = $_POST['filtro_provincia'];
         $this->filtro_ciudad = $_POST['filtro_ciudad'];
         $this->filtro_compras = $_POST['filtro_compras'];
         $this->filtro_orden = $_POST['filtro_orden'];
         
         $this->resultados = $visitante->search_for_user(
                 $this->user->admin,
                 $this->user->nick,
                 $this->filtro_query,
                 $this->filtro_perfil,
                 $this->filtro_codpais,
                 $this->filtro_provincia,
                 $this->filtro_ciudad,
                 $this->filtro_compras,
                 $this->filtro_orden
         );
      }
      else
      {
         $this->resultados = $visitante->search_for_user($this->user->admin, $this->user->nick);
      }
   }
   
   protected function public_core()
   {
      header('Location: index.php?page=community_home');
   }
   
   public function perfiles()
   {
      return array(
          'voluntario' => 'Voluntario',
          'programador' => 'Programador',
          'freelance' => 'Freelance',
          '---' => '---',
          'nomolestar' => 'No molestar',
          '---' => '---',
          'premium' => 'Premium',
          'partner' => 'Partner',
          'cliente' => 'Cliente de partner',
      );
   }
   
   public function paises()
   {
      $paises = array();
      
      $data = $this->db->select("SELECT DISTINCT codpais FROM comm3_visitantes ORDER BY codpais ASC;");
      if($data)
      {
         foreach($data as $d)
         {
            if($d['codpais'] != '')
            {
               $paises[] = $d['codpais'];
            }
         }
      }
      
      return $paises;
   }
   
   public function provincias()
   {
      $provincias = array();
      
      $data = $this->db->select("SELECT DISTINCT provincia FROM comm3_visitantes ORDER BY provincia ASC;");
      if($data)
      {
         foreach($data as $d)
         {
            if($d['provincia'] != '')
            {
               $provincias[] = $d['provincia'];
            }
         }
      }
      
      return $provincias;
   }
   
   public function ciudades()
   {
      $ciudad = array();
      
      $data = $this->db->select("SELECT DISTINCT ciudad FROM comm3_visitantes ORDER BY ciudad ASC;");
      if($data)
      {
         foreach($data as $d)
         {
            if($d['ciudad'] != '')
            {
               $ciudad[] = $d['ciudad'];
            }
         }
      }
      
      return $ciudad;
   }
   
   private function email_bloqueado($email, $rid)
   {
      $visit0 = new comm3_visitante();
      $visitante = $visit0->get($email);
      if($visitante)
      {
         if( $visitante->rid == $rid AND is_null($visitante->nick) )
         {
            return FALSE;
         }
         else if( $this->empresa->can_send_mail() )
         {
            $mail = new PHPMailer();
            $mail->CharSet = 'UTF-8';
            $mail->WordWrap = 50;
            $mail->isSMTP();
            $mail->SMTPAuth = TRUE;
            $mail->SMTPSecure = $this->empresa->email_config['mail_enc'];
            $mail->Host = $this->empresa->email_config['mail_host'];
            $mail->Port = intval($this->empresa->email_config['mail_port']);
            
            $mail->Username = $this->empresa->email;
            if($this->empresa->email_config['mail_user'] != '')
            {
               $mail->Username = $this->empresa->email_config['mail_user'];
            }
            
            $mail->Password = $this->empresa->email_config['mail_password'];
            $mail->From = $this->empresa->email;
            $mail->FromName = $this->empresa->nombre;
            
            $mail->Subject = 'Hola, tienes que iniciar sesión en facturascripts.com '.date('d-m-Y');
            $mail->AltBody = "Hola,\n\nTú o alguien ha intentado usar este email en"
                    . " facturascripts.com sin haber iniciado sesión.\n";
            
            if( is_null($visitante->nick) )
            {
               $mail->AltBody .= 'Para iniciar sesión debes usar este enlace: '
                       .'https://www.facturascripts.com/index.php?page=community_colabora&auth1='
                       .base64_encode($visitante->email).'&auth2='.$visitante->rid;
            }
            else
            {
               $mail->AltBody .= 'Tu email está vinculado al usuario '.$visitante->nick.
                    ' y por tanto debes iniciar sesión desde la sección Colabora: '
                       . 'https://www.facturascripts.com/index.php?page=community_colabora';
            }
            
            $mail->AltBody .= "\n\nAtentamente, el cron de FacturaScripts.";
            $mail->msgHTML( nl2br($mail->AltBody) );
            $mail->addAddress($email);
            $mail->isHTML(TRUE);
            
            $SMTPOptions = array();
            if($this->empresa->email_config['mail_low_security'])
            {
               $SMTPOptions = array(
                   'ssl' => array(
                       'verify_peer' => false,
                       'verify_peer_name' => false,
                       'allow_self_signed' => true
                   )
               );
            }
            
            if( $mail->smtpConnect($SMTPOptions) )
            {
               if( $mail->send() )
               {
                  $this->new_message('Se te ha enviado un email con instrucciones.');
               }
               else
                  $this->new_error_msg("Error al enviar el email: " . $mail->ErrorInfo);
            }
            else
               $this->new_error_msg("Error al enviar el email: " . $mail->ErrorInfo);
            
            return TRUE;
         }
         else
         {
            $this->new_error_msg('No se ha podido enviar el email.');
            return TRUE;
         }
      }
      else
         return FALSE;
   }
   
   private function share_extension()
   {
      $fsext = new fs_extension();
      $fsext->name = 'visitantes';
      $fsext->from = __CLASS__;
      $fsext->to = 'admin_users';
      $fsext->type = 'button';
      $fsext->text = '<span class="glyphicon glyphicon-comment"></span>'
              . '<span class="hidden-xs">&nbsp; Visitantes</span>';
      $fsext->save();
      
      $fsext2 = new fs_extension();
      $fsext2->name = 'visitante';
      $fsext2->from = __CLASS__;
      $fsext2->to = 'admin_user';
      $fsext2->type = 'tab';
      $fsext2->text = '<span class="glyphicon glyphicon-comment"></span>'
              . '<span class="hidden-xs">&nbsp; Comunidad</span>';
      $fsext2->save();
   }
   
   public function usuarios_disponibles()
   {
      $disponibles = array();
      
      if($this->user->admin)
      {
         foreach($this->user->all() as $user)
         {
            $disponibles[] = $user->nick;
         }
      }
      else if($this->visitante)
      {
         if($this->visitante->nick)
         {
            $disponibles[] = $this->visitante->nick;
         }
         
         if($this->visitante->autorizado)
         {
            $disponibles[] = $this->visitante->autorizado;
         }
         
         if($this->visitante->autorizado2)
         {
            $disponibles[] = $this->visitante->autorizado2;
         }
         
         if($this->visitante->autorizado3)
         {
            $disponibles[] = $this->visitante->autorizado3;
         }
         
         if($this->visitante->autorizado4)
         {
            $disponibles[] = $this->visitante->autorizado4;
         }
         
         if($this->visitante->autorizado5)
         {
            $disponibles[] = $this->visitante->autorizado5;
         }
      }
      
      return $disponibles;
   }
   
   public function email_from_tab()
   {
      $email = '';
      
      if( isset($_GET['snick']) )
      {
         $user = $this->user->get($_GET['snick']);
         if($user)
         {
            $email = $user->email;
         }
      }
      
      return $email;
   }
}
