<?php
/**
 * This file is part of GameQ.
 *
 * GameQ is free software; you can redistribute it and/or modify
 * it under the terms of the GNU General Public License as published by
 * the Free Software Foundation; either version 3 of the License, or
 * (at your option) any later version.
 *
 * GameQ is distributed in the hope that it will be useful,
 * but WITHOUT ANY WARRANTY; without even the implied warranty of
 * MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
 * GNU General Public License for more details.
 *
 * You should have received a copy of the GNU General Public License
 * along with this program.  If not, see <http://www.gnu.org/licenses/>.
 */

/**
 * America's Army 3 Protocol Class (Version < 3.2)
 *
 * @author Austin Bischoff <austin@codebeard.com>
 */
class GameQ_Protocols_Aa3pre32 extends GameQ_Protocols
{
	/**
	 * This class is no longer valid
	 *
	 * @var int
	 */
	protected $state = self::STATE_DEPRECATED;

	/**
	 * Array of packets we want to look up.
	 * Each key should correspond to a defined method in this or a parent class
	 *
	 * @var array
	 */
	protected $packets = array(
		self::PACKET_ALL => "\x4A\x35\xFF\xFF\x02\x00\x02\x00\x01\x00%s",
	);

	/**
	 * Methods to be run when processing the response(s)
	 *
	 * @var array
	 */
	protected $process_methods = array(
		"process_all",
	);

	/**
	 * Default port for this server type
	 *
	 * @var int
	 */
	protected $port = 39300; // Default port, used if not set when instanced

	/**
	 * The protocol being used
	 *
	 * @var string
	 */
	protected $protocol = 'aa3pre32';

	/**
	 * String name of this protocol class
	 *
	 * @var string
	 */
	protected $name = 'aa3pre32';

	/**
	 * Longer string name of this protocol class
	 *
	 * @var string
	 */
	protected $name_long = "America's Army 3 (< 3.2)";

	/*
	* Internal methods
	*/

	/**
	 * Called before the $this->packets are sent.
	 *
	 * @see GameQ_Protocols_Core::beforeSend()
	 */
	public function beforeSend()
	{
		// Encrypt the data we want to send
		$enc_data = $this->ssc_crypt("\x0A\x00playerName\x06\x06\x00query\x00", TRUE);

		// Apply this to the packet
		$this->packets[self::PACKET_ALL] = sprintf($this->packets[self::PACKET_ALL], $enc_data);

		return TRUE;
	}

	protected function preProcess_all($packets=array())
	{
	    // Check to make sure we have zlib installed
	    if(!function_exists('gzuncompress'))
	    {
	        throw new GameQ_ProtocolsException('Zlib is not installed.  See http://www.php.net/manual/en/book.zlib.php for more info.', 0);
	        return FALSE;
	    }

		// We only got one packet
		if(count($packets) == 1)
		{
			// @todo: Looking for example to test and verify

			$packets[0] =  substr($packets[0], 10);
		}
		else // Multiple Packets
		{
			$packets_sorted = array();

			// We need to sort the packets to make sure they are in the proper order
			foreach($packets AS $packet)
			{
				$packets_sorted[ord($packet[10])] = substr($packet, 14);
			}

			// Key sort the packets
			ksort($packets_sorted);
			$packets = $packets_sorted;

			unset($packet, $packets_sorted);
		}

		// Merge all the packets and decypt the data
		$data = $this->ssc_crypt(trim(implode("", $packets)), FALSE);

		// Decompress and return
		return gzuncompress($data);
	}


    protected function process_all()
    {
    	// Make sure we have a valid response
    	if(!$this->hasValidResponse(self::PACKET_ALL))
    	{
    		return array();
    	}

    	// Set the result to a new result instance
    	$result = new GameQ_Result();

    	// Let's preprocess the rules
    	$data = $this->preProcess_all($this->packets_response[self::PACKET_ALL]);

    	// Lets parse out all the data
    	if(preg_match('/attributeNames(.+)attributeValues(.+)resultCode(.*)/ism', $data, $m) === FALSE)
    	{
    		throw new GameQ_ProtocolsException("AA3 Packet response is not in a valid format");
    		return array();
    	}

    	// Init temp array
    	$tmp = array(
    		"keys" => array(),
    		"values" => array(),
    	);

    	// Pull the array into named vars
    	list($all, $keys, $values, $resultcode) = $m;

    	// Lets look at all the keys
    	$buf = new GameQ_Buffer($keys);

    	// Skip
    	$buf->skip(4);

    	while($buf->getLength())
    	{
    		// Pull out the string and strip out any junk
    		$str = preg_replace('/[^[:alnum:][:punct:]\s]/', '', trim($buf->readString(), "\x00..\x1F"));

    		// Do not continue on empty strings
    		if(strlen($str) == 0)
    		{
    			continue;
    		}

    		// Add to the temp list.  We will clean this up later
    		$tmp['keys'][] = $str;
    	}

    	// Now lets loop the values
    	$buf = new GameQ_Buffer($values);

    	// Skip
    	$buf->skip(4);

    	while($buf->getLength())
    	{
    		$str = preg_replace('/[^[:alnum:][:punct:]\s]/', '', trim($buf->readString(), "\x00..\x1F"));

    		// Do not continue on empty strings
    		if(strlen($str) == 0)
    		{
    			continue;
    		}

    		// Add to the temp list.  We will clean this up later
    		$tmp['values'][] = $str;
    	}

    	// Combine the keys and values
    	$tmp = array_combine($tmp['keys'], $tmp['values']);

    	$teams = array();
    	$team = FALSE;

    	// Let's parse the combined array and make the result.
    	foreach($tmp AS $key => $value)
    	{
    		// Is player
			if(preg_match('/^player(\D+)(\d+)$/', $key, $matches))
			{
				$result->addPlayer($matches[1], $value);

				// See if this is a team value
				if($matches[1] == 'Team')
				{
					$team = $value;
				}
				elseif ($matches[1] == 'TeamIndex' && !array_key_exists($value, $teams))
				{
					$teams[$value] = $team;
				}
			}
			else // Is server var
			{
				$result->add(substr($key, 6), $value);
			}
    	}

    	// Do the teams
    	foreach ($teams AS $teamIndex => $teamName)
    	{
    		$result->addTeam('name', $teamName);
    		$result->addTeam('index', $teamIndex);
    	}

    	unset($buf, $tmp, $data, $keys, $values, $resultcode, $matches, $teams);

    	return $result->fetch();
    }

    /**
     * encrypt|decrypt the buffer.
     *
     * @param string $buffer
     * @param bool $encrypt
     */
    protected function ssc_crypt($buffer, $encrypt = FALSE)
    {
    	$master_key = pack("H*",
    		"f5c5914b27235dc0dc274200ddd187c32fe02aed5fc5c079518f49208e4c5548aaef313c5d2e7c91dc580d3cd9e1aec577595325d3c5c84b44a020802becb17e".
    		"7d6b6b87e8a4ebc8e4cafbaf5720f9600818b334ad2695ba0f19e1fbd48d0139f05e9059e98a15c79ebabb4f3aa8039d8720aef2bf1b4693a67a20a114b8505b".
    		"693cf5b24a236503582ecdb8109a7d89a8d90d660b96435b4656ecec3fff2086e94c54988843d2aa55adefb2d47fc804c0024a7897e993b2326e8990e425f7c8".
    		"38aef55f2002f22d84479f43849de260a8a2de6a7de09225c275a172729e65be687182bde68cb17b3fd77bf513c8045f0b6696d3a501b255db0632e36c0e7806".
    		"c5c193b5b9a9c621f0ac9a0ee72196edbb336e7431b75eba95d02191048ab7c3874578218d79a2623e308184fdac98a1568c09b8907d8411e29c53823a3a68bc".
    		"c785547ebb29401822da7fa59c6fc412cf2a9201f31336bcdffe78501058b1d7814e920ceee7aca8fa798f10f0a8ba19a1deae864e1c77f974880e5571a4380b".
    		"52d3357ec8cbf8ff6ff7e8f3fa6223f923e4a7bb1918054bcd2a115e466307f39d964c051983f8b2e5db0b39332ec08c94d9b36a4594ab5e868bc888e4586687".
    		"b6e62b2bb06ad0903544e379d744896f95346a0238b2b72c6d38ed1bf011185bad1910812cfe2c5b38db10433088f2e5a3746e7302467d35e8f07722fad1f7d4".
    		"283fbea23fa6f50f710491b1f0a8dd3a187939e7f344de57c256ffb063791fc556d3791570a873537c3f05f8ca08aa1eb2e3f641e0fb46fde7394f8fb4c216d7".
    		"55c020b405a21b8e4340136fc9583800afd87a677d3d9b6b95585ba502d6db2dec504f25b612340e29be64700682f4f012908e2672916ba83d35deb58d826d83".
    		"d75a61f726876747d78df10a31f6acb36cb64dec47b7da11c7e7177dcc097965a50065e8e5f91732e20647604c00c0fa451f7ee140d93515b7b5e6f9e0c92ad0".
    		"29648ab1e0ea363c5a19d12832c54c0ae67baa7e029217ede5f97cd07ebf3aaf14c020f4646e3792e2472409299868b9ee1ce7a69a30203218289523d848a2ee".
    		"42b96edf05f24182491dfb048c17f815aa8983d9ab72723defbe9750cd694bc1318c92862ed7b7ab1e37472b986a7f4745224fd723e4e6ef53ff6d5f51f1b8cd".
    		"34b32b9ac92968e5ec8b631aa750e7cec51e7fddca5da1cdc836c0243ab2a2f86d072479c117738fafba4d72db6fee13274d652a7c76ff962c1389b32f95f3c0".
    		"04d178b71646fe084507e7dd4b4db98405cb72399f78f989c188fb2ed6e18e5aa417adae504d33ad8414f9e3a6e466837062e8ea91664f63134539679b119d6b".
    		"3918f833ceddc249933b0ae83e0965b38fb86d3da02622d02f57c7282e5f0cdb18f71e7450c538ddca55588575f80754dd0c89840bcf7e246e8f041309069f15".
    		"a49c27fa0a5913c72be881ae27ff6b0332701d96dc295576d2a9bc0fd266f5604da647f78d1c2ced95c4cf8a929c55bf524198898b444c67040d7c7debcc3cc9".
    		"7cab1a8fe190f4db097beaccea9a34e38380b43bd2b2bf98f471c02894aaaf3944680988497aa74d293238d503a4df19d90af204fdcbb1875170a96b7f3e288c".
    		"0f24e1c8b9ce4f77f2b03944c2abbacba69331a244923c38f731f368d10eca82dd503bdece016064c68cb38a4e3408712959cb5216dc42bf5365eb789c484bcc".
    		"5813a1f1680fc5606e8da06bd5a68a73bd593fcd4aeb9aca06bb258f84a38dd0d4c6c0c355c4d5e0e1a97abaa11869f26285a99db4dfb8eab0b0f53e80d2486b".
    		"9a6cc63affac0b830b12434ddbc1c4ef3ee46af67fcc711b88a352d2b324c0acfb35bfbe74865afd7f3293a944cd9f69230a206c5112ed9858497ddc118c0338".
    		"63f1a974b033a225c74e83c9d1bec1a3e6a7b2b7ddab58aec40fe4bed9e2fd1beaded608c695dafaf4d683fdf3b9175d1283d7d99b47c40209a555c317e29bad".
    		"574ac49e78ae91896b527d27f04d89b10d5f754b953d1218bf01fc06086c031ff334eab692e9c6fb221ac0f3027283ac5350d860f2d6125d31edf4b7ac806f21".
    		"abeb04f84230e8c17455e54a27d6862cfb3279370eae1cdb1f84c10209e89241182c307b45a6b97520a62bc263c66f78d27b52ad9728f5d78c1626297b1d1cdd".
    		"e47fd67d9f1f4846a3643810359f2cc6b22a662683836eb48f6e1605be3a830fe29f0c54412e7d82aefff9748a2fddb368dd0103161e2a17da69216e22adf6b5".
    		"7ce255e400279188655820eedd5a1935aa3d8cf621fa312bab89cbb3071bfbe7e0635126de8217bd5c342f35824511769ac6b72de09b87012cd85f2cbef53e11".
    		"9aba484771b15bddda183501230ae6a16fcde55a161df16f178e04478a3711437dc91eeabe92e14b44d2f49036532be42c425346df9d91288aa409a63272e061".
    		"baaaca491cc04c44b2ac739290baa76d9fdc7b66733548af6411a6ba790c4962ddf033e63fab462bc0ccbfa45d45ce377d32f4c7e905cab5fbbb524f8c2907d0".
    		"41b304d1f38f348efd34a7d51c118445d05353b5f0449f368450782df457ca55169bdfa817a94e1082faf4115cf3d6d890481affb2feb95145691f152485465d".
    		"0f8dba4cde2079784574fadbe805222e3a132934f1a419cda032b310fd7dfa2830d3f3385d646ba0c373cba4d624a6267300014cdd2dd5e87999aa5b0e5df0a8".
    		"de50f3473918474ccf82f9c8ab9f31379a9d8d00bead3bc8b9d00f4ebba9c7b0ea882454e3a785e096d7887b3a507f089dba88925df12c633241ed2f9f68905b".
    		"66775d1d0ca3cc312f7be8641856be8de24248e55dd737df8410e23e9457024f534261f09ab278821b1c89da824f7f546a4163f4d53ccf07ee9bd59adb673822".
    		"87092b94a7847141a796a6abf90f7bfa5d8967bfba2275283863bfc3f8283f0e5b223748a55dff04f3c6bf228bb1e0bfd2c80289abf5819e165268b4e687bcf4".
    		"a33f1c42c47a6236ca14c26778ad2cbe013c20807e45276d49a4e0df7df7c42d2c73f298f61fc8e778ba953a71c6b7d1779624552df0f3896a790671a3a981fa".
    		"17914d856321d0997ff4b2d05944335ceac60b63b1d827eab5ef7483990e9bd1b5453a473e1efd476ba1e093466cb21dc72e35dc12bb8c8d3bb29db420251590".
    		"32441b8a7e9458cad9cdc1551ce52312bb27d858a8ae319e525b38f20242a60933b2a21bd858e147cc6ee702983c84bf535d1575a54dc46c03cdb42a39d1a64e".
    		"433d9bea41f9915f7d9d462d4308baccb19bb1adc3e0125715950f7c7f8b54312826204fd512386da587bad7bf81069dc554fd8fd77153832225e56a7fa4046b".
    		"d588ed258dc7e54ccf1c021f9800376376bdcfc62116555ab0e06b3161b3b7a6a7a87de2371215207c43fce54c82feddc5d444b08f6a30c0095007d526da1b02".
    		"41563a9360f86ef3b824294bd174679f4dee74912acdeb00ac96a713ad86dc212a544b7420fa6c83d5dec48400e1f11f8163e20c932bc893820a8261939e0f85".
    		"fdb416c6a0a18cc0182d675702a8362694f23ce686962150f862357fe84a0b572068c7e0578909d7f82c87cd17e7ef50e5566eab694ac76edb4b6d8a85cd2910".
    		"0b93272b0a524a24db8db7d4622fae63d982e4090fb519e30736d5b5152d58a234919d216d0294628841cba91ed72d985ba92f7cc548378e7ddf812816ad99dd".
    		"27adffdf5b6d762a79a942d8af9a8f0ac81afc98869dcdcc06835478947ced5ccbb22d02624e207c774042fa8c133221c362bef69582c52ca9c014db1ec2d351".
    		"a1d72bb01c06e32ca0a4ecfe923737f0f7145b27c943a9be1f174dd46d3af58e7a2f612177affd11ae7e1b9231aadb46bcb732ee79de7e62f467721f06d8e9e5".
    		"59b526bb702ddbc0f0b46a2162458c15c0154cbb1b1edad3fa198a0781279ecc5e5391269c335bc94b2f21da781cf943cd0e700206128fe1f1e3af4e70bfbaec".
    		"1c7ae4884c7e7544050036b001f87fc2f10762888701c160010e7691ea2b53b646d22178ebf1a56eb9cba86ffa2b570d846e231037d403298103c61732b04113".
    		"ff7ec74e0a671332f7df9da231f995c1fb53523c17c23105312b7d8ab63e5f6a0e7b9d106f3ce575d14befb3a5803aabcc9edb5f1ddf9dcabff4efbd785b169d".
    		"f7fb1b991faf63f064b5fc8f2c7fcac4b35a61f19c92dec36a6aadf02dc3942dde51d7225aefeaf6b7527183c2adc832c6bc8735bc7be2c18ad3d70653f91581".
    		"ce42a275ef6715932ae7513d0ecb726be54c167cc89445a08cb8e12fc583aee815b3947bd1ac781fcbfbdda25fe3e931a21c47058197ceffbe9bd2ac6394b2d5".
    		"95c3e10076c3aceba33b1556029edfbc04849e0d66713f7beeb1517dcd43279a5073ec9fa221bfaceef0f639e771a44156778cbb696af28e2437eea3fc025d27".
    		"70b1409d978e4ec808c58288d525ac977db0ace80d9554925bf8767b8e91a9bf1ed25deabdbb93315ca08f711ae3f768a911eeacd93bfa6db3957da83c0fd945".
    		"a7e596b66530aa7347e04590fd31db6b49485a9ea8208c0aab4068f482b185aaed6ee69e32f9ff7b882763da34f6e3bce94c79353ef6849d47e6345d8727e076".
    		"f1aa0133c2399e4d777525fe9aa29e75d23df6e829f9058580413d5c24f85568beb1343430f393adee28ab54e220b4c884fa6ebc2825705f863ba7d82977f653".
    		"edb2088abd84ad52a1810a52abc6e7c3b5687f3bf4744941ce48c876205f2497b641e6e4bb565ab816425c348e1f034104efda9a21723b00cdadc6ed2af6b225".
    		"524ae512afba6bc19c471e14bbba042dba641424005a816f25aee44ee84cf2f729b79b1b9d58218f0274d92168c9bb1cd1c141b5f8341a3a4dc78c0ddf08dfd4".
    		"110b4eb0b71b265fe70aa5a4b2186cafad5ff94dafd5b4b4560bac45cb47c4c863274ac2d84af46b75bfde496d39984ff0af8ab7d98bc12c02ce782b23268d03".
    		"864826b0201d8d1e0c09c9ab229a2f7fe1504795bafa8b8ae13fb046a2f35233a49b772b57862ada835951742439693ed9f3a080aea7a1309de4ae04b1ce3d78".
    		"72cdd85a3544906afaf55aff8255bdb2367c7ecf184c91c8f4c60a1301b80f8bb9f0ff6d80ac6e1c9d6c9fafbc65199790e0a9c323e68b105f5c56eed2f60294".
    		"5ab59d79698829ba092cc97f37dd023595d3fa014e718cda23d6bdbbfd70c2c6cc1b9121d22eae0bde7b94277dc8e5e096d60351f2740ddb986c7e10e0af8a40".
    		"e9bd526f863cde028dd253e18013d3c76c2006a9ab9ec3e7b6b1aca865b2ace8c8debb50ae1efbc0e49dd69f128c28bd02d79f22717e2679d5142540733cb278".
    		"0969944106122d5f2baf97f7e09ef67b894cd191411126ad962e4b9c5a0bbe83215563662ce5f063ce2a76c2e09613539fbb094d389e739ca0a3fc34bd1692ba".
    		"f0601e2122a70fdf68ede6c431090896622362c59801000727718f4b551f32340fc5f740e15fc0a023791aa57a6cc97af3077f5d71d33cbc864049b30cb11ea5".
    		"23c15141ea5ac620aec5f81e6661bf8f01a3c817ac1ab592570b63764402e4934d776df03cadae448c5d9082c30c00737e4bbe5c184a1167507d9b99bdd05592".
    		"456ac25dadb5beafe282028611db969c44db7bfb2cad349c0ecbebc281a00ad4f70cfd889b3533833ab845f86403e6a1970da6b5c8b8e82e9f42a82c7c14e535".
    		"16b3d9efbaae6ca6b9c93977f17f58ec29a1a8bb188fb15f377bf50d37e84781ca1716052f657a361cbe44eb227002a57390873e54b8695f76fe0f84f873e021".
    		"c92945f3d7b54861be3c237701c140c3a4e1b84fa4bab910cd265393e0172293d6fc40fa1872e175d7d3f06153a9eca3f8db85c2166f68415eda3bf4aee35adc".
    		"0231cd6cfe5d3a23b51fb0105176b9cdadc28304d27fef698cf4155235d07ecfaf5a2c5f8610a63ee809b0e0260251c33873dceebdda1ec3725d1376031e45cc".
    		"731a870b39edc97b549b96624c891984acf7a422584bc56f2104256f15da552d0a8376a546b6966153728ca1f38514df0d458375e99bc01fa498b07abb33803f".
    		"da07c4149e6e5773f9ec65ac3c87ca7c515f263de3cda2d53edbc20c47486ee33f9810c8226bbc9c52fcadb1f01fe28bf099b8afb9f1798e0b9815210c559187".
    		"c562b5e45350a5d0708c2fb96bad405ef4b8b535066ed02da198e4a3a4eaf075450c87f6d9840c8e00b8e316bcc7a5c6113fefbd72b0c7f6860fcecc8a3f33fb".
    		"a2999e4f3f3e3da5d7bfcf5d22a93f4d16ae6dd053685dfc7223628f92086735d09551bd29e8d0f537d06f33536fce8360d7443f583e9079685efce0347c1ffa".
    		"fedd0b7d1125f0dfc9bb21460079f286abbbeb549bb744aeea0b7a6bc66a272c8af945621b57b8380d40fa067c3060b9d44b79bd4333ec96d47632124a9aad0a".
    		"2df287eda9312f70f12f544fd7bdef9e6cc5e110effb8dbdebb821571f0fa95301db9da0bb60b77af6d5b7de00ca26039f1dda92f7a777c75d02fc340f1b81b5".
    		"e7c5efc6aaa6ffe3b77db348b7a5973a9465cb1e01841fa10f398318bfb73a4f8f53a4bded656f35db0ef00685826d8eac3aa0941623b3401ffdaba927bc91f4".
    		"808818548a60f653e9f340f79e40d666525923c4847ac3c0a9b36f3069620b0aea677ee7afa2c333987d9a5afade1b0e1e22ef7470228b07c9f482a6c343a37c".
    		"462a749c02d4cc86447cc16c3c68955afa80e63a3a41aaa1375c7ca0cffa0335e96e599e1b6841ae5693b5fa6ff437c3c1dca20075b7a58aafa81845af0aa8f6".
    		"30520d89a362d667447045c2b39f88f573f6b76b95ea4a98950ad797570b841975e9841306223dbefd21a4f092d69452c4539c664e27e110622ae7a7db5073d6".
    		"17eb023b36f28a13eeeebdbd964df63dcb18762950b6bd3eeead2a25b9bba48060ac8b82af3f41ecafbb7134140ca8cc687b92eded8bdabd9567e50950ed617a".
    		"a114d3db8648f9ab48a622456aec56fe79cfa6225fc7fd3fb0607f9dbc1bd861b316600fc10163fe8098ea685bc3fe06435f51cb1ce7ffebae67b3114fadf8c8".
    		"808a4044bb06638d05bc9a73c44c5b1eb7c83cdb4bde51ffa85413a97fbd534ddb17dc899fc4e2ced6ed81eeb117b4c77f9ecd03251367649a5649ec58567907".
    		"4fc8c2702dc42a58308f4023fb2cd30c79ecb9a952cde77dfcf92d8ef234811c327112abd568c49d4bf693f611d07e433fcd0a396530c6a279eb3ba567d780b7".
    		"271b6bfc7f1683a6b9159e143788662e8c5f73dd25ab623633efe781edd647b32003c9f3eaf236d968244e4561bc855848b839bfb93af2ea3e230a30089230c4".
    		"2e593ed3b9be53d677a7c9da744ee1961aaccac237f9e0bc1f886a92d5f335c6c0b0250ea76fbdcd85ae9cf6afe7ab25fd6b4753be6505b986757b003b94a089".
    		"d6a42b1fb24d2249ec917bb0ad50c8bd31265f82071a0816c3f8985edf0311205f83eaf8ff5587a3c7c24938a3f0cf9ff438b567d71407a51292e6d7e3f939e6".
    		"cdbecd49e913793f73cb964406934907ca4d48f44bec301bdf0110986757fcac6c2cca84eb7c5fad1662d1a833d24fa356771d6b772759a4837d9872d23ff1ab".
    		"219597aadc062f317d6cbc044bf65dc5ddda95ddc34d68584b7db991c8441a43e0511f71b88dda141f36b7cb326650c3244b989f1b992d2baa318e2a76dd1c34".
    		"a946c843255f65c6896eac3a6774ceff50b6f66b752672f5ce8dc84149ba6b227da844254d01bf470f6c987e8b5df2168414bcee11ad8c131d16e43addbdd493".
    		"595117f4f211c5d6460ee1be41e72b42c21252ce6dcd9838e53b0e1fd8d1864c2d3d219b82d42d0446865848431658732a78f0d9348f8044fa7f576d11562d25".
    		"d7b681f714c4b43532543d27069a21d1d152e646c56d75229bb198f87676108306e68fa49751f3b1d678bbf1ea38b2e0712d896882b5ea1494136f23a7e1d528".
    		"ca456c6c2a2cfc8cb6b6e7e6526aaa1da082653492b624936213569892706d8f9c6496b1193ec5a4294e3c1da14b25c24337cf9bb3490ea3f8a54e0a5b9f77af".
    		"fc70fe8dcb7687a9f45c7ae3ee8f2a94fa58e6c920cce1f447fd60526fa71b6f1048a3dcc7680e3b20ac66d78290bfc3878e72d4876e014036b0b80b6be4bf2e".
    		"a358125bea811b51af76a0077b3a615750a9ca3368d1d17e060a0d37bfd3b13c91412ca83298b06aea3048607f718c04667dcfc7faa4ac5a594be1c1551140ba".
    		"9c1ea7cebc074b1fbd338eef831fa3eb1f39088bcf1cf13bf706b1d287e12b165f4fb3e6c4586067c5e2f461c4cc86400b456428e8767c1b57a7bc3e64a8abe6".
    		"d253646f8796763b2a33de35c6f1667d06f30bb12c0fd0e28e4859ebdc2f96236af4a895d9a7d6fb90cbb60084db28a0c628faf7653c316ec69b5c5103aea495".
    		"792efd58ec42bc950f8608d5fa6834aab7bd2aaece33b3e16756f518a5410e8957dd534437e8c152451d86beb20124e8fb9e672d13fb7e98e153c124fdb2eaf7".
    		"f94a23efffeea25ec31f821e492d9de00a6d056c67e565f734f864d425035bb13620b7a1f44ec02ab7a6b1c4a38511b6902cfcf199d3918eb07da11d634add44".
    		"0860d123fa2b8003f87270777c6415e32f1b34dd6e1e22df3a78684e1169fce84b61cf461544f4e891fcd9d1f5a1e5fef148aeddbfcc922f5d7bfd3bd2480e8a".
    		"3318c75ce0afc24ca179fc0e832ab64368c174407bf2cd45a72cd5c9e7dd0b9def7500cec54d4d692938a1bb18289189d4b2445640d8abc9a0b70c3ffc8ba3c8".
    		"d483119a4f63851a57cf30f48c88616785a5ee00cb9221db45dd8dff118ca33bb4ae254937891f2c971edc8614fa3fc43e56f297a44a234fb1737f23d44a15f0".
    		"6a9e364fe1daa8e28bf72927526296202713f76dc8342e3843483b479ff793697b11a934bdc206905dd020e2f321cf8d65c245a8e7c4275f87301211800f0751".
    		"4e9cb59b88540f5441e6b09b4b73112d855ba0dffd4affd670c4f76ec11ac07a6cc2201ac65c83b3b3e4dc10d991ef4424cd001d34f0393dc262957df641469a".
    		"e00f74c527f8c99f50432c5ff4c4260ec6998b7ef2a0223290762126542d8aa89bfd241ac59e3a9a6c6f13afc9d69a771d124d16359525e4b374605b699e32bd".
    		"fb393d9397767bce32ab2d5557d05c33fa54183b0d5facc73a097441aa34abf7d6ac36fb35d6be7f19d0c26c7ad564c06f8a4f616ff4819c53e8b29e782b8791".
    		"c4039e5d049bd36819ae6d01a113eae6260e25150b935ee364011558dea97e1ee0e7f2938b7368ad9a5a86bae4f89a9ffbd06638566a785cb6ad3982b133ce6a".
    		"3edb13aa2c4ad4db7052ac646fcf336b375efb6a360d448862f2b711db3d8e657a706c14013664beae06b1a067fd078b0a8800c01dd610d583bee4fa4634e4f3".
    		"5251372b8144a7194ed60dc2539283ce909e7d65338a9050b09b66b647f30b6d595d7e03d9a77029afce140df7717f64949ae1362f94602dc2e70840e3117ab1".
    		"a26cc8e8ffd068ec225f0b75b2de63e3511f4485c87fb0087e4421675f3754bc4bc9c0a38db6392661e8a59802d83f887cf81aa99ed13a10b4b8a176144f76ce".
    		"3a192cc77b09e3f8a087db488f3d304d048623f46a031ba9251896cd08ff601dd0b933f5110b4cc9d943b5705b2435fa1c0adaad6c3aed88022f57cc3d71048f".
    		"9d5f420cfaf737b8a9f2434601b296b14384618fa9b76e6acbf1b55ad7130f582f36920a5aff71e15d120b11d6e0dd374554803538f3b12305512cf24322ed52".
    		"cd7ce5f409efd2f2752684bc326bf4548fa17169028c819ba342ee672682860a6de09752f509caad897484160895dc712b70bd05d588fe218fd85718b9b833ff".
    		"2c18e2566416ce1e52c3d7dc696cca1ad02b9b99e2953f92d8fe7ac0e4d75bd2ae2834b9ad8e87f179cdaf5e75609abdf1236787fe366347c32991f20c7faf41".
    		"b65da4ed5edc3cab1134a4ee0a3b565cab7c6dcd6f93feb528ddf0a1e992f6ad4814e51d338433dc5b52fddd8e780a312d12c80c4dbdaf8818b1c84883d8be41".
    		"186de5fdeeb9c7b7542a8429e53645a313cd8c9a53c3790b9fcf0143421da3bb586762790c91b0110f68b5fd111338560437d7d77457fb5587efb40a90ed1c02".
    		"838ba4e83b0c6adb175d94b6e14767a4f4a127e80f79be7741f4dc446c520176fd5b0412cc4d7a8f3d293e438d50e4e79e52bbc2c3bc6707d97b6289f1b39733".
    		"48c9351b66be55b2152bee9b76c42dc057d12134180488f45aee9491fe72f8634e3beeda8006869a829d2d58614150ab489dca7af268c09dde668cc20428ff88".
    		"366a3c0119446bdba29c39b0723fcd639393d397d138ab241c187beac647d8f73e5e42b3468e3958e0e73908c081ce0b6c894f0409f3bd321807a1633860a8e7".
    		"49cb4a10875a65b3f0a073f48f141747c88afe9039ef0795752dbd07ef51a2dadb40bb09bb9d4fcb328f68af28f8d76085fccaef4afe848a93c4cac43f55863a".
    		"21b540e6d408eb55fdfbd2a0c13fbae6fdf68e51423737f6966105d1ed57570bb521adb9576b06988d7d5a6445fe77d177076d47ca45b437a9780b376d49689e".
    		"6b0be983d90f46dbf935e14b53f3bf7ac7aec7fc1b92c14f161e59ae2620f7552206f22a365c91476943b8b51e920661efc19d040070407ba1cf011d3a0e072e".
    		"68d10e064619aa2184d7e848729b254af6b83db15fca2134d0d54efc761fff25c1169d608ed2434de8ae3cafb8c3af0b5b23a16183b5ead5dc5d175c955f4db5".
    		"454623d611244c462776118992ba03e8e20e6e1d9d6101d2286d7e040d5a56f22d6e3ae86bd6a0605c8b34d7a385fee5f3c9b6d0cf550f7aa67f338d8a014dfd".
    		"639cade855e8d25df73ea01bc5635bb5e032269b2a10f6b2baea7c4a88ede42caf91d7c9d3b2802608fdc361e23ee8cdcc1c954da86f929e9721130ef6d74e99".
    		"180f8c8c2263b41f538e105bc5f411f8dd1c2d3e0dc4540ff9cbdb9a6c44524ebcdfe37d9427a43dc24fd28c2fc25baef96490ae847b435ef4eea87db030829d".
    		"06b4c5d9271c8ffda114c336f5d82f9e6ca0d140112f364b1613cfe84c6e924629cba51a7d21f92ce26802bda0651340a8aad0c1ef439acc5552634304321cf6".
    		"02851751630d671a8cce7028f1cc6fdbce64f762c8ed522c2a81c2886986999a85d41a87d2ba5281dcbc2dbd728559470017e12fd70a97a771de499d2953c49b".
    		"0e60abac5ced203dd26bb75df922938723b1341bb07b0250d7af1bf91788994f8ed193221dd829e6665b114763e490fd8482955b097ac3b5b124bf92ae8ce902".
    		"1897b67db820cbfd646fe2c61e63baa972651a47bb1aae56f5e623a1167beff84166ea78cc9854b21a9478ebf3a1429226213c20a7a9ce8031eced508b937263".
    		"1357591069d5c482c0f6f99e4a6084f34fdab7b26399b4efcb0e5217e4e9115d0f6011bcfe55e0f05d3d8850febab0a6100bab8142a3913662a568f9d32367bf".
    		"5db46b6572cb76bd6a49d84bd567e1f834bbd705dd395c1609e9eba7fe8b9c59f1c4cb2561461204805c25a384140314e515f84050949529050279393884f8d0");

    	$game_key = "c6mw4it2kg7sz5o0813d9qyufenhj\x00";

    	$buffer_length   = strlen($buffer);
    	$game_key_length = strlen($game_key);

    	// We want to encrpt the data
    	if ($encrypt)
    	{
    		for ($i=1; $i<$buffer_length; $i++)
    		{
    			$buffer[$i] = chr(ord($buffer[$i]) ^ ord($buffer[$i-1]));
    		}

    		for ($i=0; $i<$buffer_length; $i++)
    		{
    			$buffer[$i] = chr(ord($buffer[$i]) ^ ord($game_key[($i % 128) % $game_key_length]) ^ ord($master_key[$i % 128]) ^ ord($master_key[$i]));
    		}
    	}
    	else // We need to decrypt the data
    	{
    		for ($i=0; $i<$buffer_length; $i++)
    		{
    			$buffer[$i] = chr(ord($buffer[$i]) ^ ord($master_key[$i]) ^ ord($master_key[$i%128]) ^ ord($game_key[($i%128) % $game_key_length]));
    		}

    		for ($i=($buffer_length-1); $i>0; $i--)
    		{
    			$buffer[$i] = chr(ord($buffer[$i]) ^ ord($buffer[$i-1]));
    		}
    	}

    	return $buffer;
    }
}
