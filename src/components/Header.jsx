import { useEffect, useState } from 'react';
import { Link } from 'react-router-dom';
import { useRoot } from "../context/RootContext.jsx";
import './Header.css';
import Menu from '../components/Menu.jsx';


function Header(props) {
  const { user, authLoading, openModal } = useRoot();
  const [menu, setMenu] = useState(false); 
  const menuChanger = () => setMenu(prev => !prev);
  return (
	<>
		<div id='header'>
			<div id='header-contents'>
				<img id="menu" src="/sky/menu.png" onClick={() => menuChanger()}/>
				<Link to='/' id='toback'>
					<span className="logo"><img src="/sky/sky.png" id='logo' /></span>
					<span id='pagename'>Sky</span>
				</Link>
				<div>
				</div>
				<div id='l'>
				{props.nonbut ? (
						<div></div>
						) : authLoading ? (<div className='spinner'></div>) : user ? (<Link className='lau' to="/profile">{user}</Link>) : (<Link className='lau' to="/register">Регистрация / Вход</Link>)
				}
				</div>
			</div>
			<div id="menu-window-place">
				{
					menu ? (
						<Menu />
					) : (
						<> </>
					)
				}
				</div>
		</div>
	</>
  );
}

export default Header;