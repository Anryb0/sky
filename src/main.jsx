import { StrictMode } from 'react';
import { createRoot } from 'react-dom/client';
import './index.css';
import App from './App.jsx';
import { BrowserRouter as Router } from 'react-router-dom';

import { ModalProvider } from './context/ModalContext.jsx';
import Modal from './components/Modal.jsx';

createRoot(document.getElementById('root')).render(
  <StrictMode>
    <ModalProvider>
      <Router basename="/sky">
        <App />
        <Modal />  
      </Router>
    </ModalProvider>
  </StrictMode>
);
