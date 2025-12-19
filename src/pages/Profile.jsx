import { useEffect, useState } from 'react';
import { Link, useNavigate } from 'react-router-dom';
import Header from '../components/Header.jsx'
import './Profile.css';
import { useRoot } from '../context/RootContext.jsx';

function Profile(){
	const navigate = useNavigate();
	const { user, authLoading, openModal } = useRoot();
	const[loading,setLoading] = useState(true);
	const[response,setResponse] = useState(null);
	function logout(){
		let xhr = new XMLHttpRequest();
		xhr.open("GET", "https://anryb0.ru/sky/api/logout.php", true);
		xhr.withCredentials = true;
		xhr.send(null);
		xhr.onload = function(){
			if(xhr.status == 200){
				let response = JSON.parse(xhr.responseText);
				navigate('/');
			}
			else{
				openmodal('Ошибка ' + xhr.status + ' при проверке авторизации', true);
			}
		};
	}
	useEffect(()=>{
		let xhr = new XMLHttpRequest();
		xhr.open("GET", "https://anryb0.ru/sky/api/profile.php", true);
		xhr.withCredentials = true;
		xhr.send(null);
		xhr.onload = function(){
			if(xhr.status == 200){
				let response = JSON.parse(xhr.responseText);
				setResponse(response);
				setLoading(false);
			}
			else{
				openModal('Ошибка ' + xhr.status + ' при получении данных профиля', true);
			}
		};
	},[])
	if(!authLoading && !user){
		navigate('/');
	}
	return (
		<>
			<Header nonbut='true'/>
			<main>
			{loading ? (<div className='spinner center'></div>) : ( 
				<div className='glassy' id='maininfo'>
					<p><b>Привет, {user}</b></p>
						<div className='glassy'>Ваша почта: {response.email}</div>
						{ response.confirmed ? (<div className='glassy'>Учетная запись подтверждена ✔</div>) : (<div className='glassy error'>Учетная запись не подтверждена ❌. Проверьте почту</div>)
						}
					<button onClick={logout}>Выйти из аккаунта</button>
				</div>)}
			</main>
		</>
	)
}

export default Profile;