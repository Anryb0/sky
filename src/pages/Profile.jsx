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
	function sendNewLink(){
		let xhr = new XMLHttpRequest();
		xhr.open('GET','https://anryb0.ru/sky/api/makenewlink.php');
		xhr.withCredentials = true;
		xhr.send();
		xhr.onload = function(){
			let response = JSON.parse(xhr.responseText);
			if(response.success){
				openModal('Письмо успешно отправлено');
			}
			else{
        openModal(response.message,true);
      }
		} 
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
				<div id='maininfo'>
					<h3><b>Привет, {user}</b></h3>
						<>
						{ response.confirmed ? (<div className='glassy t'>✔️ Учетная запись подтверждена<span className='right'>Ваша почта: <b>{response.email}</b></span></div>) : (<div className='glassy error'>Ваша почта: <b>{response.email}</b><span className='right'>❌ Учетная запись не подтверждена. Проверьте почту</span><button className='green' onClick={sendNewLink}>Отправить еще ссылку</button></div>)
						}
						{
							response.ip ? (<div className='glassy t'>✔️ Сеть настроена<span className='right'><button onClick={() => {window.location.href ="https://anryb0.ru/sky/api/downloaduserconfig.php"}}>Скачать VPN конфигурацию</button><button onClick={() => {window.location.href ="https://openvpn.net/client/"}}>Скачать OpenVPN Connect</button></span></div>) : (<div className='glassy'>❌ У вас пока нет VPN конфигураций</div>)
						}
						{
							response.servers.length > 0 ? (<><h3>Мои сервера</h3>
								<div id='stop'><span>Название</span><span>IP</span><span>Статус</span><span>Тариф</span><span>ОС</span></div>
								<hr />
								{response.servers.map((item)=> {
									return (<div className='glassy ilist s'><b>{item.name}</b><span>10.8.0.{item.ip}</span><span>{item.status}</span><span>{item.pname}</span><span>{item.oname}</span></div>)
								})}</> 
							) : (<div className='glassy t'>❌ У вас пока нет серверов<span className='right'><button onClick={() => {navigate('/start')}}>Создать</button></span></div>)
						}
						</>
					<button onClick={logout} className='error'>Выйти из аккаунта</button>
				</div>)}
			</main>
		</>
	)
}

export default Profile;