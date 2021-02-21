#https://medium.com/@gustavo.uach/how-to-build-a-simple-job-server-in-codeigniter-712d979940d8
#https://expressionengine.com/forums/archive/topic/93795/how-to-integrate-mail-queue-with-codeigniter
#https://github.com/izn/codeigniter-mailqueue

CREATE TABLE `newsletters` (
  `uid` int(11) NOT NULL auto_increment,
  `created` datetime NOT NULL,
  `sent` datetime NOT NULL,
  `subject` varchar(100) default NULL,
  `html` text,
  `queued` tinyint(1) NOT NULL default '0',
  `startNum` int(11) NOT NULL,
  PRIMARY KEY  (`uid`)
) ENGINE=MyISAM  DEFAULT CHARSET=utf8;


CREATE TABLE `newsletter_subscribers` (
  `uid` int(11) NOT NULL auto_increment,
  `email` varchar(100) NOT NULL,
  `subscriberRef` varchar(32) NOT NULL,
  `active` tinyint(1) NOT NULL,
  PRIMARY KEY  (`uid`)
) ENGINE=MyISAM  DEFAULT CHARSET=utf8;





<?php

class newsletter_model extends Model {

  function newsletter_model()
  {
    parent::Model();
  }
    
        function getQueuedNewsletters()
    {
        $this->db->where('queued', '1');    
        return $this->db->get('newsletters');
    }
    

    function getFirstQueuedMailing(){
        $this->db->where('queued', '1');
        $this->db->order_by('uid', 'asc');
        return $this->db->get('newsletters',1)->row();
    }    

    function getBatchNewsletterSubscribers($startNum,$quant)
    {
        $this->db->where('active', '1');
        $this->db->orderby('uid', 'asc');
        return $this->db->get('newsletter_subscribers',$quant,$startNum); 
        // remember! codeigniter does LIMIT the opposite way round : numRows, startRow
    }
    
    function countNewsletterSubscribers()
    {
        $query = $this->db->get('newsletter_subscribers');
        return $query->num_rows();
    }
        
        
        
        
     
   <?php

class SendNewsletter extends Controller {
    
    function SendNewsletter()    {
        parent::Controller();
    }
    
    function index(){
        
        $this->load->model('newsletter_model');
        $this->load->library('email');
        
        $quant = 10; // number of newsletters to send each time
        
        // are there any newsletters queued?
        if($this->newsletter_model->getQueuedNewsletters()->num_rows > 0)
        {
            // get the ID of the FIRST item in queue
            $newsletterData = $this->newsletter_model->getFirstQueuedMailing();
            
            $newsletterID = $newsletterData->uid;

            $subscribers = $this->newsletter_model->getBatchNewsletterSubscribers($newsletterData->startNum,$quant);
            
            $totalSubscribers = $this->newsletter_model->countNewsletterSubscribers();
            
            foreach ($subscribers->result() as $address) {
            
                $this->email->clear(); // clear anything that's already here
                
                $config['mailtype'] = 'html';
            $config['charset'] = 'utf-8';
            $this->email->initialize($config);
                
                $this->email->to($address->email); // the email address of the subscriber
                $this->email->from('from_address@test.com');
                $this->email->subject($newsletterData->subject);
                $this->email->message($newsletterData->html);
                $email_sent = $this->email->send();
            } 
            
            // update the newsletter table with the new startNum    
            $updateStartNum = $this->newsletter_model->updateNewsletterStartNum($newsletterID, $quant, $totalSubscribers);
        
        } // end check for queued newsletters
    }
    
}




        
        
        
        
        
        
        
        
        
    function updateNewsletterStartNum($newsletterID, $quant, $totalSubscribers)
    {
        $this->db->where('uid', $newsletterID);
        $this->db->set('startNum', 'startNum+'.$quant, FALSE);
        $this->db->update('newsletters'); 
        
        if($this->getNewsletter($newsletterID)->startNum >= $totalSubscribers)
        {
            $currentTime = date("Y-m-d H:i:s");
            $this->db->where('uid', $newsletterID);
            $this->db->set('startNum', '0', FALSE);
            $this->db->set('queued', '0', FALSE);
            $this->db->set('sent', $currentTime);
            $this->db->update('newsletters');
        }
    }
}

?>



