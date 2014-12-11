namespace BB\Models;

class Model extends \BB\Observable {
	public $userState = null;
	
	public function post( $data = array(), &$errors = array() ) {
		$user = null;
		$post = null;
		
		if ( isset( $data['user'] ) ) {
			$user = $this->initUser(
				$data['user']['username'],
				$data['user']['password'],
				$data['user']['email']
			);
			$this->attach( $user );
			$this->notify();
			
			if ( count( $user->errors ) ) {
				$errors['user'] = $user->errors;
				return false;
			}
			
			$this->detach( $user );
		}
		$post = $this->initPost(
			$data['post']['title'],
			$data['post']['body'],
			$data['post']['parent'],
			$data['post']['id']
		);
		
		$this->attach( $post );
		$this->notify();
		
		if ( count( $post->errors ) ) {
			$errors['post'] = $post->errors;
			return false;
		}
		
		// We're done with the post
		$this->detach( $post );
		
		if ( $post->id && $user->id ) {
			
		}
		
		return true;
	}
	
	protected function initUser( 
		$username, 
		$password, 
		$email
	) {
		$user = new User();
		$user->username	= $username;
		$user->password	= $password;
		
		if ( empty( $email ) ) {
			$this->userState	= 'login';
		} else {
			$user->email		= $email;
			$this->userState	= 'register';
		}
		
		return $user;
	}
	
	protected function initPost( 
		$title, 
		$body, 
		$root, 
		$parent, 
		$id = 0 
	) {
		$post = new Post();
		$post->title		= $title;
		$post->raw 		= $body;
		$post->root_id		= $root;
		$post->parent_id	= $parent;
		
		if ( $id > 0 ) {
			$post->id = $id;
		}
		
		return $post;
	}
}
