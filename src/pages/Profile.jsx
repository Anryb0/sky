import { useEffect, useState } from 'react';
import { Link, useNavigate } from 'react-router-dom';
import Header from '../components/Header.jsx'
import './Profile.css';
import { useModal } from '../context/ModalContext.jsx';

function Profile(){
	const navigate = useNavigate();
	const { openmodal } = useModal();
	function logout(){
		let xhr = new XMLHttpRequest();
		xhr.open("GET", "https://anryb0.ru/sky/api/logout.php", true);
		xhr.withCredentials = true;
		xhr.send(null);
		xhr.onload = function(){
			if(xhr.status == 200){
				let response = JSON.parse(xhr.responseText);
				openmodal('квакваква', true);
				console.log(response)
			}
			else{
				openmodal('Ошибка ' + xhr.status + ' при проверке авторизации', true);
			}
		};
	}
	return (
		<>
			<Header />
			<main>
				<button onClick={logout}>Выйти из аккаунта</button>
			</main>
		</>
	)
}

export default Profile;