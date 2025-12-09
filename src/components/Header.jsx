import { useEffect, useState } from 'react';
import { Link } from 'react-router-dom';
import './Header.css';


function Header(props) {
	const [login,setLogin] = useState(null);
	const [loading,setLoading] = useState(true);
	useEffect(() => {
		const xhr = new XMLHttpRequest();
		xhr.open("GET", "https://anryb0.ru/sky/api/login_check.php", true);
		xhr.withCredentials = true;
		xhr.send(null);
		xhr.onload = function(){
			if(xhr.status == 200){
				let response = JSON.parse(xhr.responseText);
				console.log(response);
				if(response.authorized){
					setLogin(response.name)
				}
			}
			else{
				openmodal('Ошибка ' + xhr.status + ' при проверке авторизации', true);
			}
			setLoading(false)
		};
	},[]);
  return (
	<>
		<div id='header'>
			<div id='header-contents'>
				<Link to='/' id='toback'>
					<span className="logo"><img src="/sky/sky.png" id='logo' /></span>
					<span id='pagename'>Sky</span>
				</Link>
				<div id='l'>
				{loading ? (<div className='spinner'></div>) : props.nonbut ? (
						<div></div>
						) : login ? (<Link className='lau' to="/profile">{login}</Link>) : (<Link className='lau' to="/register">Регистрация / Вход</Link>)
				}
				</div>
			</div>
		</div>
	</>
  );
}

export default Header;