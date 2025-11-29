import { useEffect, useState } from 'react';
import { Link } from 'react-router-dom';
import './Header.css';


function Header(props) {
  return (
	<>
		<div id='header'>
			<div id='header-contents'>
				<Link to='/' id='toback'>
					<span className="logo"><img src="/sky/sky.png" id='logo' /></span>
					<span id='pagename'>Sky</span>
				</Link>
				<div id='l'>
					<Link className='lau' to="/register">Регистрация / Вход</Link>
				</div>
			</div>
		</div>
	</>
  );
}

export default Header;