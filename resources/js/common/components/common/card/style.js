import Styled from 'vue3-styled-components';
const props = [];
const divProps = ['bgColor'];

const CardWidget = Styled('figure', props)`
display: flex;
margin: 0;
border-radius: 1px solid black;
position: relative;
h2,
p {
  margin: 0;
}
figcaption {
  .more {
	position: absolute;
	top: 0px;
	left: 0;
	a {
	  color: #888;
	}
  }
  h2 {
	font-size: 28px;
	font-weight: 800;
	letter-spacing: -0.5px;
  }
  p {
	font-size: 13px;
	font-weight: 600;
	color: #8c8c8c;
	margin-top: 2px;
  }
}
`;

const CardWidgetIcon = Styled('div', divProps)`
  width: 56px;
  height: 56px;
  border-radius: 16px;
  display: flex;
  align-items: center;
  justify-content: center;
  background: ${props => props.bgColor ? props.bgColor : '#7b2a72'};
  margin-right: 18px;
  box-shadow: inset 3px 3px 7px rgba(0,0,0,0.15), inset -3px -3px 7px rgba(255,255,255,0.2);
`;



export { CardWidget, CardWidgetIcon };
