import { useEffect, useState } from 'react';
import { Link } from 'react-router-dom';
import { useRoot } from "../context/RootContext.jsx";
import './Menu.css';

function Menu(){
	return (
		<div id='menu-window' className='glassy'>
			<Link className = 'ilist glassy mlink' to="/">Главная страница</Link>
			<Link className = 'ilist glassy mlink' to="/profile">Личный кабинет</Link>
			<Link className = 'ilist glassy mlink' to="/start">Конфигуратор</Link>
			<Link className = 'ilist glassy mlink' to="/support">Поддержка</Link>
		</div>
	)
}

export default Menu;